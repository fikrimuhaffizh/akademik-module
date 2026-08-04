<?php

namespace Modules\Akademik\Services;

use Modules\Kurikulum\Models\KurikulumMataKuliah;
use Modules\Akademik\Models\PenawaranMataKuliah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection as SupportCollection;

class PenawaranService
{
    public function getBaseQuery(): Builder
    {
        return PenawaranMataKuliah::query()
            ->with(['periodeAkademik', 'kurikulumMataKuliah.mataKuliah'])
            ->select(['penawaran_id', 'tenant_id', 'periode_akademik_id', 'kurikulum_mata_kuliah_id', 'prodi_id', 'is_aktif', 'created_at', 'updated_at'])
            ->orderByDesc('created_at');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();
        if (! empty($filters['periode_akademik_id'])) {
            $query->where('periode_akademik_id', decryptIdIfEncrypted($filters['periode_akademik_id']));
        }
        return $query;
    }

    public function searchForSelect2(?string $search = null, ?int $periodeAkademikId = null, int $limit = 20): SupportCollection
    {
        return PenawaranMataKuliah::query()
            ->with('kurikulumMataKuliah.mataKuliah')
            ->where('is_aktif', true)
            ->when($periodeAkademikId, fn (Builder $query) => $query->where('periode_akademik_id', $periodeAkademikId))
            ->whereHas('kurikulumMataKuliah.mataKuliah', function (Builder $query) use ($search) {
                if (! $search) {
                    return;
                }
                $query->where(function (Builder $q) use ($search) {
                    $q->where('kode', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                });
            })
            ->limit($limit)
            ->get()
            ->map(function (PenawaranMataKuliah $penawaran) {
                $mk = $penawaran->kurikulumMataKuliah?->mataKuliah;
                if (! $mk) {
                    return null;
                }
                return [
                    'id' => (string) $penawaran->penawaran_id,
                    'text' => trim($mk->kode . ' - ' . $mk->nama),
                    'penawaran_id' => $penawaran->penawaran_id,
                    'mata_kuliah_id' => $mk->mata_kuliah_id,
                    'kode' => $mk->kode,
                    'nama' => $mk->nama,
                ];
            })
            ->filter()
            ->unique('penawaran_id')
            ->values();
    }

    public function getAll(): Collection { return PenawaranMataKuliah::with(['periodeAkademik', 'kurikulumMataKuliah.mataKuliah'])->get(); }
    public function findById(string|int $id): PenawaranMataKuliah { return PenawaranMataKuliah::with(['periodeAkademik', 'kelasKuliahs', 'kurikulumMataKuliah.mataKuliah'])->findOrFail(decryptIdIfEncrypted($id)); }

    public function create(array $data): PenawaranMataKuliah
    {
        return DB::transaction(function () use ($data) {
            KurikulumMataKuliah::findOrFail($data['kurikulum_mata_kuliah_id']);
            $entity = PenawaranMataKuliah::create($data);
            logActivity('perkuliahan', 'Menambah penawaran MK', $entity);
            return $entity;
        });
    }

    public function update(string|int $id, array $data): PenawaranMataKuliah
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);
            KurikulumMataKuliah::findOrFail($data['kurikulum_mata_kuliah_id']);
            $entity->update($data);
            logActivity('perkuliahan', 'Memperbarui penawaran MK', $entity);
            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);
            logActivity('perkuliahan', 'Menghapus penawaran MK', null);
            return $entity->delete();
        });
    }
}
