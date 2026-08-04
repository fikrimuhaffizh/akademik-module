<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\PeriodeAkademik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PeriodeAkademikService
{
    public function getBaseQuery(): Builder
    {
        return PeriodeAkademik::query()->select(['periode_akademik_id', 'tenant_id', 'nama', 'tahun_mulai', 'tahun_selesai', 'semester', 'tgl_mulai', 'tgl_selesai', 'is_aktif', 'created_at', 'updated_at'])->orderByDesc('tgl_mulai');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();
        $search = is_array($filters['search'] ?? null) ? ($filters['search']['value'] ?? '') : ($filters['search'] ?? '');
        if (! empty($search)) {
            $query->where('nama', 'like', "%{$search}%");
        }
        return $query;
    }

    public function getAll(): Collection { return PeriodeAkademik::orderByDesc('tgl_mulai')->get(); }
    public function getAktif(): ?PeriodeAkademik { return PeriodeAkademik::where('is_aktif', true)->first(); }
    public function findById(string|int $id): PeriodeAkademik { return PeriodeAkademik::findOrFail(decryptIdIfEncrypted($id)); }

    public function create(array $data): PeriodeAkademik
    {
        return DB::transaction(function () use ($data) {
            $entity = PeriodeAkademik::create($data);
            logActivity('perkuliahan', sprintf('Menambah periode akademik: %s', $entity->nama), $entity);
            return $entity;
        });
    }

    public function update(string|int $id, array $data): PeriodeAkademik
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);
            $entity->update($data);
            logActivity('perkuliahan', sprintf('Memperbarui periode akademik: %s', $entity->nama), $entity);
            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);
            logActivity('perkuliahan', sprintf('Menghapus periode akademik: %s', $entity->nama), null);
            return $entity->delete();
        });
    }
}
