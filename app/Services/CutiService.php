<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\Cuti;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CutiService
{
    public function getBaseQuery(): Builder
    {
        return Cuti::query()->orderByDesc('created_at');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();
        if (! empty($filters['mahasiswa_id'])) {
            $query->where('mahasiswa_id', decryptIdIfEncrypted($filters['mahasiswa_id']));
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query;
    }

    public function getAll(): Collection
    {
        return Cuti::orderByDesc('created_at')->get();
    }

    public function findById(string|int $id): Cuti
    {
        return Cuti::findOrFail(decryptIdIfEncrypted($id));
    }

    public function create(array $data): Cuti
    {
        return DB::transaction(function () use ($data) {
            $entity = Cuti::create($data);
            logActivity('mahasiswa', sprintf('Menambah cuti mahasiswa ID: %d', $entity->mahasiswa_id), $entity);
            return $entity;
        });
    }

    public function update(string|int $id, array $data): Cuti
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);
            $entity->update($data);
            logActivity('mahasiswa', 'Memperbarui cuti mahasiswa', $entity);
            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);
            logActivity('mahasiswa', 'Menghapus cuti mahasiswa', null);
            return $entity->delete();
        });
    }
}
