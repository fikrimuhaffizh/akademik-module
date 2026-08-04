<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\PembimbingMahasiswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PembimbingMahasiswaService
{
    public function getBaseQuery(): Builder
    {
        return PembimbingMahasiswa::query()->with(['periodeAkademik', 'jenisPembimbing'])->select(['pma_id', 'tenant_id', 'periode_akademik_id', 'pegawai_id', 'mahasiswa_id', 'jenis_pembimbing', 'created_at', 'updated_at'])->orderByDesc('created_at');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();
        if (! empty($filters['periode_akademik_id'])) {
            $query->where('periode_akademik_id', decryptIdIfEncrypted($filters['periode_akademik_id']));
        }
        return $query;
    }

    public function getAll(): Collection { return PembimbingMahasiswa::all(); }
    public function findById(string|int $id): PembimbingMahasiswa { return PembimbingMahasiswa::findOrFail(decryptIdIfEncrypted($id)); }

    public function create(array $data): PembimbingMahasiswa
    {
        return DB::transaction(function () use ($data) {
            $entity = PembimbingMahasiswa::create($this->normalizeData($data));
            logActivity('perkuliahan', 'Menambah pembimbing mahasiswa', $entity);
            return $entity;
        });
    }

    public function update(string|int $id, array $data): PembimbingMahasiswa
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);
            $entity->update($this->normalizeData($data));
            logActivity('perkuliahan', 'Memperbarui pembimbing mahasiswa', $entity);
            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);
            logActivity('perkuliahan', 'Menghapus pembimbing mahasiswa', null);
            return $entity->delete();
        });
    }

    private function normalizeData(array $data): array
    {
        $idFields = ['periode_akademik_id', 'pegawai_id', 'mahasiswa_id', 'jenis_pembimbing'];
        foreach ($idFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = decryptIdIfEncrypted($data[$field]);
            }
        }
        return $data;
    }
}
