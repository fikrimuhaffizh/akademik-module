<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\Biodata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BiodataService
{
    public function getBaseQuery(): Builder
    {
        return Biodata::query()->orderBy('mahasiswa_id');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();
        if (! empty($filters['mahasiswa_id'])) {
            $query->where('mahasiswa_id', decryptIdIfEncrypted($filters['mahasiswa_id']));
        }
        return $query;
    }

    public function getAll(): Collection
    {
        return Biodata::orderBy('mahasiswa_id')->get();
    }

    public function findById(string|int $id): Biodata
    {
        return Biodata::with('mahasiswa')->findOrFail(decryptIdIfEncrypted($id));
    }

    public function findByMahasiswa(int $mahasiswaId): ?Biodata
    {
        return Biodata::where('mahasiswa_id', $mahasiswaId)->first();
    }

    public function create(array $data): Biodata
    {
        return DB::transaction(function () use ($data) {
            $entity = Biodata::create($data);
            logActivity('mahasiswa', sprintf('Menambah biodata mahasiswa ID: %d', $entity->mahasiswa_id), $entity);
            return $entity;
        });
    }

    public function update(string|int $id, array $data): Biodata
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);
            $entity->update($data);
            logActivity('mahasiswa', 'Memperbarui biodata mahasiswa', $entity);
            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);
            logActivity('mahasiswa', 'Menghapus biodata mahasiswa', null);
            return $entity->delete();
        });
    }
}
