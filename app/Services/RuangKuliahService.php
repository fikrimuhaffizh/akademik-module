<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\RuangKuliah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RuangKuliahService
{
    public function getBaseQuery(): Builder
    {
        return RuangKuliah::query()->select(['ruang_id', 'tenant_id', 'kode', 'nama', 'gedung', 'lantai', 'kapasitas', 'jenis', 'is_aktif', 'created_at', 'updated_at'])->orderBy('kode');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();
        $search = is_array($filters['search'] ?? null) ? ($filters['search']['value'] ?? '') : ($filters['search'] ?? '');
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")->orWhere('nama', 'like', "%{$search}%");
            });
        }
        if (! empty($filters['jenis'])) {
            $query->where('jenis', $filters['jenis']);
        }
        return $query;
    }

    public function getAll(): Collection { return RuangKuliah::orderBy('kode')->get(); }
    public function findById(string|int $id): RuangKuliah { return RuangKuliah::findOrFail(decryptIdIfEncrypted($id)); }

    public function create(array $data): RuangKuliah
    {
        return DB::transaction(function () use ($data) {
            $entity = RuangKuliah::create($data);
            logActivity('perkuliahan', sprintf('Menambah ruang kuliah: %s', $entity->kode), $entity);
            return $entity;
        });
    }

    public function update(string|int $id, array $data): RuangKuliah
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);
            $entity->update($data);
            logActivity('perkuliahan', sprintf('Memperbarui ruang kuliah: %s', $entity->kode), $entity);
            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);
            logActivity('perkuliahan', sprintf('Menghapus ruang kuliah: %s', $entity->kode), null);
            return $entity->delete();
        });
    }
}
