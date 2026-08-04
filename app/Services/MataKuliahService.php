<?php

namespace Modules\Akademik\Services;

use Modules\Kurikulum\Models\KurikulumMataKuliah;
use Modules\Akademik\Models\PenawaranMataKuliah;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Penyedia opsi Mata Kuliah untuk modul lain (mis. Lab).
 *
 * Sejak refactor "Kurikulum = single source of truth" (tabel akper_mata_kuliah
 * di-drop), sumber MK adalah kur_mata_kuliah yang diakses lewat pivot
 * kur_kurikulum_mata_akd. Service ini mengembalikan bentuk sama dengan
 * implementasi lama agar kontrak ke Lab tidak berubah:
 *   - id / mata_kuliah_id  = kur_mata_akd.mata_kuliah_id
 *   - kode, nama, text
 */
class MataKuliahService
{
    public function searchForSelect2(?string $search = null, ?int $periodeAkademikId = null, int $limit = 20): SupportCollection
    {
        $query = KurikulumMataKuliah::query()->with('mataKuliah');

        if ($periodeAkademikId) {
            $kurMkIds = PenawaranMataKuliah::where('periode_akademik_id', $periodeAkademikId)
                ->pluck('kurikulum_mata_kuliah_id');
            $query->whereIn('kur_mk_id', $kurMkIds);
        }

        if ($search) {
            $query->whereHas('mataKuliah', function ($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        return $query->limit($limit)->get()
            ->map(function (KurikulumMataKuliah $kurMk) {
                $mk = $kurMk->mataKuliah;
                if (! $mk) {
                    return null;
                }
                return [
                    'id' => (string) $mk->mata_kuliah_id,
                    'text' => trim($mk->kode . ' - ' . $mk->nama),
                    'mata_kuliah_id' => $mk->mata_kuliah_id,
                    'kode' => $mk->kode,
                    'nama' => $mk->nama,
                ];
            })
            ->filter()
            ->unique('mata_kuliah_id')
            ->values();
    }

    public function getSelect2OptionsByIds(array $ids): SupportCollection
    {
        if (empty($ids)) {
            return collect();
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        return KurikulumMataKuliah::query()->with('mataKuliah')
            ->whereIn('mata_kuliah_id', $ids)
            ->get()
            ->map(function (KurikulumMataKuliah $kurMk) {
                $mk = $kurMk->mataKuliah;
                if (! $mk) {
                    return null;
                }
                return [
                    'id' => (string) $mk->mata_kuliah_id,
                    'text' => trim($mk->kode . ' - ' . $mk->nama),
                    'mata_kuliah_id' => $mk->mata_kuliah_id,
                    'kode' => $mk->kode,
                    'nama' => $mk->nama,
                ];
            })
            ->filter()
            ->unique('mata_kuliah_id')
            ->values();
    }
}
