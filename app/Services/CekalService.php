<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\Cekal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CekalService
{
    public function getBaseQuery(): Builder
    {
        return Cekal::query()->select(['cekal_id', 'tenant_id', 'mahasiswa_id', 'jenis', 'alasan', 'tgl_mulai', 'tgl_selesai', 'is_aktif', 'created_at', 'updated_at'])->orderByDesc('created_at');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();
        if (! empty($filters['mahasiswa_id'])) {
            $query->where('mahasiswa_id', decryptIdIfEncrypted($filters['mahasiswa_id']));
        }
        if (! empty($filters['jenis'])) {
            $query->where('jenis', $filters['jenis']);
        }
        if (isset($filters['is_aktif'])) {
            $query->where('is_aktif', $filters['is_aktif']);
        }
        return $query;
    }

    public function getAll(): Collection { return Cekal::orderByDesc('created_at')->get(); }
    public function findById(string|int $id): Cekal { return Cekal::findOrFail(decryptIdIfEncrypted($id)); }

    public function isCekal(int $mahasiswaId, ?string $jenis = null): bool
    {
        $query = Cekal::where('mahasiswa_id', $mahasiswaId)->where('is_aktif', true);
        if ($jenis) {
            $query->where('jenis', $jenis);
        }
        return $query->exists();
    }

    public function create(array $data): Cekal
    {
        return DB::transaction(function () use ($data) {
            $entity = Cekal::create($data);
            logActivity('mahasiswa', sprintf('Menambah cekal mahasiswa ID: %d', $entity->mahasiswa_id), $entity);
            return $entity;
        });
    }

    public function update(string|int $id, array $data): Cekal
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);
            $entity->update($data);
            logActivity('mahasiswa', 'Memperbarui cekal mahasiswa', $entity);
            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);
            logActivity('mahasiswa', 'Menghapus cekal mahasiswa', null);
            return $entity->delete();
        });
    }
}
