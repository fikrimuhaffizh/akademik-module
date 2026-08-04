<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\StatusSemester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StatusSemesterService
{
    public function getBaseQuery(): Builder
    {
        return StatusSemester::query()->orderByDesc('semester_ke');
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
        return StatusSemester::orderByDesc('semester_ke')->get();
    }

    public function findById(string|int $id): StatusSemester
    {
        return StatusSemester::findOrFail(decryptIdIfEncrypted($id));
    }

    public function create(array $data): StatusSemester
    {
        return DB::transaction(function () use ($data) {
            $entity = StatusSemester::create($data);
            logActivity('mahasiswa', sprintf('Menambah status semester mahasiswa ID: %d, semester: %d', $entity->mahasiswa_id, $entity->semester_ke), $entity);
            return $entity;
        });
    }

    public function update(string|int $id, array $data): StatusSemester
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);
            $entity->update($data);
            logActivity('mahasiswa', 'Memperbarui status semester mahasiswa', $entity);
            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);
            logActivity('mahasiswa', 'Menghapus status semester mahasiswa', null);
            return $entity->delete();
        });
    }
}
