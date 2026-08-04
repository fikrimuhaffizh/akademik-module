<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\Nilai;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Modules\Akademik\Models\MataKuliah;
use Modules\Akademik\Services\RekognisiService;

class NilaiService
{
    public function getBaseQuery(): Builder
    {
        return Nilai::query()
            ->with(['mahasiswa', 'mataKuliah', 'kelas', 'periodeAkademik'])
            ->orderBy('periode_akademik_id');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();

        if (! empty($filters['mahasiswa_id'])) {
            $query->where('mahasiswa_id', decryptIdIfEncrypted($filters['mahasiswa_id']));
        }

        if (! empty($filters['periode_akademik_id'])) {
            $query->where('periode_akademik_id', decryptIdIfEncrypted($filters['periode_akademik_id']));
        }

        return $query;
    }

    public function getAll(): Collection
    {
        return $this->getBaseQuery()->get();
    }

    public function findById(string|int $id): Nilai
    {
        return $this->getBaseQuery()->findOrFail(decryptIdIfEncrypted($id));
    }

    public function upsertFinal(array $data, string $sourceType = Nilai::SOURCE_IMPORT_MANUAL, ?string $sourceReference = null): Nilai
    {
        return DB::transaction(function () use ($data, $sourceType, $sourceReference) {
            return Nilai::updateOrCreate(
                [
                    'tenant_id' => $data['tenant_id'] ?? tenantId(),
                    'mahasiswa_id' => $data['mahasiswa_id'],
                    'kelas_id' => $data['kelas_id'] ?? null,
                    'mata_kuliah_id' => $data['mata_kuliah_id'],
                    'periode_akademik_id' => $data['periode_akademik_id'],
                ],
                [
                    'nilai_angka' => $data['nilai_angka'] ?? null,
                    'nilai_huruf' => $data['nilai_huruf'] ?? null,
                    'bobot' => $data['bobot'] ?? null,
                    'sks' => $data['sks'],
                    'is_lulus' => (bool) ($data['is_lulus'] ?? false),
                    'source_type' => $sourceType,
                    'source_reference' => $sourceReference,
                    'published_at' => $data['published_at'] ?? now(),
                ]
            );
        });
    }

    public function getKhs(int $mahasiswaId, int $periodeAkademikId): Collection
    {
        return $this->getFilteredQuery([
            'mahasiswa_id' => $mahasiswaId,
            'periode_akademik_id' => $periodeAkademikId,
        ])->get();
    }

    public function getTranskrip(int $mahasiswaId): Collection
    {
        $nilaiLms = $this->getBaseQuery()
            ->where('mahasiswa_id', $mahasiswaId)
            ->where('is_lulus', true)
            ->get();

        $rekognisi = app(RekognisiService::class)
            ->getApprovedForTranskrip($mahasiswaId);

        return $nilaiLms
            ->map(function ($row) {
                $row->sumber_nilai = $row->source_type === Nilai::SOURCE_PUBLISH_LMS ? 'lms' : 'manual';

                return $row;
            })
            ->concat($rekognisi)
            ->values();
    }

    public function hitungIpk(int $mahasiswaId): float
    {
        return $this->hitungMutu($this->getTranskrip($mahasiswaId));
    }

    public function hitungIps(int $mahasiswaId, int $periodeAkademikId): float
    {
        return $this->hitungMutu($this->getKhs($mahasiswaId, $periodeAkademikId));
    }

    public function getForKelulusan(int $mahasiswaId): Collection
    {
        return $this->getTranskrip($mahasiswaId);
    }

    private function hitungMutu(Collection $nilai): float
    {
        $totalSks = (int) $nilai->sum('sks');

        if ($totalSks === 0) {
            return 0.0;
        }

        return round($nilai->sum(fn ($row) => (float) $row->bobot * (int) $row->sks) / $totalSks, 2);
    }

    /**
     * Check if mahasiswa has passed a prerequisite MK (by Kurikulum MK ID).
     * Maps prasyarat_mk_id (kurikulum) → akper_mata_kuliah via kur_mata_kuliah_id → checks akmhs_nilai_akhir.
     */
    public function hasLulusPrasyarat(int $mahasiswaId, int $prasyaratMkId): bool
    {
        $akperMkIds = MataKuliah::where('kur_mata_kuliah_id', $prasyaratMkId)
            ->pluck('mata_kuliah_id');

        if ($akperMkIds->isEmpty()) {
            return true; // No Perkuliahan MK mapped — cannot enforce
        }

        return Nilai::where('mahasiswa_id', $mahasiswaId)
            ->whereIn('mata_kuliah_id', $akperMkIds)
            ->where('is_lulus', true)
            ->exists();
    }
}
