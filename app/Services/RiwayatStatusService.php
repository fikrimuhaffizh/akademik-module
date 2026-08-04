<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\RiwayatStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RiwayatStatusService
{
    public function getBaseQuery(): Builder
    {
        return RiwayatStatus::query()->orderByDesc('tgl_efektif');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();
        if (! empty($filters['mahasiswa_id'])) {
            $query->where('mahasiswa_id', decryptIdIfEncrypted($filters['mahasiswa_id']));
        }
        if (! empty($filters['status_baru'])) {
            $query->where('status_baru', $filters['status_baru']);
        }
        return $query;
    }

    public function getAll(): Collection
    {
        return RiwayatStatus::orderByDesc('tgl_efektif')->get();
    }

    public function findById(string|int $id): RiwayatStatus
    {
        return RiwayatStatus::findOrFail(decryptIdIfEncrypted($id));
    }

    public function create(array $data): RiwayatStatus
    {
        return DB::transaction(function () use ($data) {
            $entity = RiwayatStatus::create($data);
            logActivity('mahasiswa', sprintf('Menambah riwayat status mahasiswa ID: %d → %s', $entity->mahasiswa_id, $entity->status_baru), $entity);
            return $entity;
        });
    }

    public function update(string|int $id, array $data): RiwayatStatus
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);
            $entity->update($data);
            logActivity('mahasiswa', 'Memperbarui riwayat status mahasiswa', $entity);
            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);
            logActivity('mahasiswa', 'Menghapus riwayat status mahasiswa', null);
            return $entity->delete();
        });
    }
}
