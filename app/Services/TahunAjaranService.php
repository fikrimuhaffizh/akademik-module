<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TahunAjaranService
{
    public function getBaseQuery(): Builder
    {
        return TahunAjaran::query()->select(['tahun_ajaran_id', 'tenant_id', 'nama', 'tahun_mulai', 'tahun_selesai', 'is_aktif', 'created_at', 'updated_at'])->orderByDesc('tahun_mulai');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();
        $search = is_array($filters['search'] ?? null) ? ($filters['search']['value'] ?? '') : ($filters['search'] ?? '');
        if (! empty($search)) {
            $query->where('nama', 'like', "%{$search}%");
        }
        if (isset($filters['is_aktif'])) {
            $query->where('is_aktif', $filters['is_aktif']);
        }
        return $query;
    }

    public function getAll(): Collection { return TahunAjaran::orderByDesc('tahun_mulai')->get(); }
    public function getActive(): ?TahunAjaran { return TahunAjaran::where('is_aktif', true)->first(); }
    public function findById(string|int $id): TahunAjaran { return TahunAjaran::findOrFail(decryptIdIfEncrypted($id)); }

    public function create(array $data): TahunAjaran
    {
        return DB::transaction(function () use ($data) {
            $entity = TahunAjaran::create($data);
            logActivity('perkuliahan', sprintf('Menambah tahun ajaran: %s', $entity->nama), $entity);
            return $entity;
        });
    }

    public function update(string|int $id, array $data): TahunAjaran
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);
            $entity->update($data);
            logActivity('perkuliahan', sprintf('Memperbarui tahun ajaran: %s', $entity->nama), $entity);
            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);
            logActivity('perkuliahan', sprintf('Menghapus tahun ajaran: %s', $entity->nama), null);
            return $entity->delete();
        });
    }
}
