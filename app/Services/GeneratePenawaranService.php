<?php

namespace Modules\Akademik\Services;

use Modules\Kurikulum\Services\KurikulumService;
use Modules\Akademik\Models\PenawaranMataKuliah;
use Modules\Akademik\Models\PeriodeAkademik;
use Modules\Akademik\Models\PublishBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GeneratePenawaranService
{
    /**
     * Generate Penawaran MK langsung dari Kurikulum (single source of truth).
     *
     * Alur:
     *   1. Tentukan kurikulum aktif prodi via KurikulumService (kur_setting_prodi -> is_published).
     *   2. Map periode.semester (ganjil/genap) -> semester kurikulum (1-8).
     *   3. Ambil kur_kurikulum_mata_kuliah di semester target via KurikulumService.
     *   4. Buat Penawaran (periode+prodi) bila belum ada, bawa is_wajib/grup_pilihan.
     *   5. Audit batch publish ke akper_publish_batch.
     *
     * Penawaran yg SUDAH ada (termasuk yg sengaja di-nonaktif) TIDAK dibuat ulang.
     */
    public function __construct(protected KurikulumService $kurikulumService) {}

    public function generate(int $periodeId, int $prodiId): array
    {
        $periode = PeriodeAkademik::findOrFail($periodeId);

        $kurikulum = $this->kurikulumService->getActiveKurikulumForProdi($prodiId);
        if (! $kurikulum) {
            $msg = 'Kurikulum belum di-publish & di-apply untuk prodi ini (kur_setting_prodi). Publish & apply kurikulum dulu di modul Kurikulum.';
            logActivity('perkuliahan', 'Gagal Generate Penawaran MK: ' . $msg, null);
            return ['created' => 0, 'skipped' => 0, 'errors' => [$msg]];
        }

        $targetSemesters = $this->mapSemester($periode->semester);
        if (empty($targetSemesters)) {
            $msg = 'Mapping semester untuk periode "' . $periode->semester . '" belum didukung.';
            logActivity('perkuliahan', 'Gagal Generate Penawaran MK: ' . $msg, null);
            return ['created' => 0, 'skipped' => 0, 'errors' => [$msg]];
        }

        $kurMks = $this->kurikulumService->getMataKuliahsBySemesters($kurikulum->kurikulum_id, $targetSemesters);

        $tenant = sys_tenant_id();
        $created = 0;
        $skipped = 0;
        $createdIds = [];

        DB::transaction(function () use ($kurMks, $tenant, $periodeId, $prodiId, &$created, &$skipped, &$createdIds) {
            foreach ($kurMks as $kurMk) {
                // Hormati penawaran yg sudah ada (termasuk yg di-uncheck).
                $exists = PenawaranMataKuliah::where([
                    'tenant_id' => $tenant,
                    'periode_akademik_id' => $periodeId,
                    'kurikulum_mata_kuliah_id' => $kurMk->kur_mk_id,
                    'prodi_id' => $prodiId,
                ])->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $penawaran = PenawaranMataKuliah::create([
                    'tenant_id' => $tenant,
                    'periode_akademik_id' => $periodeId,
                    'kurikulum_mata_kuliah_id' => $kurMk->kur_mk_id,
                    'kurikulum_kode' => \Modules\Kurikulum\Models\Kurikulum::find($kurMk->kurikulum_id)?->kode_kurikulum,
                    'prodi_id' => $prodiId,
                    'is_aktif' => true,
                    'is_wajib' => $kurMk->is_wajib,
                    'grup_pilihan' => $kurMk->grup_pilihan,
                ]);
                $createdIds[] = $penawaran->penawaran_id;
                $created++;
            }
        });

        // Audit satu batch publish (berhasil maupun conflict/skip).
        if ($created > 0 || $skipped > 0) {
            $batch = PublishBatch::create([
                'tenant_id' => $tenant,
                'source_module' => 'kurikulum',
                'reference_code' => 'GEN-PEN-' . $periodeId . '-' . $prodiId . '-' . Str::random(8),
                'total_data' => $kurMks->count(),
                'success_count' => $created,
                'conflict_count' => $skipped,
                'metadata' => [
                    'periode_akademik_id' => $periodeId,
                    'prodi_id' => $prodiId,
                    'kurikulum_id' => $kurikulum->kurikulum_id,
                    'semester' => $periode->semester,
                    'target_semesters' => $targetSemesters,
                    'created_ids' => $createdIds,
                ],
            ]);

            logActivity(
                'perkuliahan',
                "Generate Penawaran MK berhasil: {$created} dibuat, {$skipped} dilewati (periode_id={$periodeId}, prodi_id={$prodiId}).",
                $batch
            );
        } else {
            logActivity(
                'perkuliahan',
                "Generate Penawaran MK: tidak ada MK baru dibuat (periode_id={$periodeId}, prodi_id={$prodiId}). Semua sudah ada.",
                null
            );
        }

        return ['created' => $created, 'skipped' => $skipped, 'errors' => []];
    }

    /**
     * Map periode.semester -> semester kurikulum (1-8, absolut).
     * ganjil -> {1,3,5,7}; genap -> {2,4,6,8}; pendek -> semua.
     */
    protected function mapSemester(string $periodeSemester): array
    {
        return match ($periodeSemester) {
            'ganjil' => [1, 3, 5, 7],
            'genap' => [2, 4, 6, 8],
            'pendek' => [1, 2, 3, 4, 5, 6, 7, 8],
            default => [],
        };
    }
}
