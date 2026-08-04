<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\PembebananDosen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PembebananService
{
    public function getBaseQuery(): Builder
    {
        return PembebananDosen::query()
            ->with(['kelas.penawaran.kurikulumMataKuliah.mataKuliah'])
            ->select([
                'pembebanan_id',
                'tenant_id',
                'kelas_id',
                'pegawai_id',
                'peran',
                'created_at',
                'updated_at',
            ])
            ->orderByDesc('created_at');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();

        if (! empty($filters['kelas_id'])) {
            $query->where('kelas_id', decryptIdIfEncrypted($filters['kelas_id']));
        }

        if (! empty($filters['pegawai_id'])) {
            $query->where('pegawai_id', decryptIdIfEncrypted($filters['pegawai_id']));
        }

        return $query;
    }

    public function getAll(): Collection
    {
        return PembebananDosen::with(['kelas', 'kelas.penawaran.kurikulumMataKuliah.mataKuliah'])->get();
    }

    public function findById(string|int $id): PembebananDosen
    {
        return PembebananDosen::with(['kelas', 'kelas.penawaran.kurikulumMataKuliah.mataKuliah'])
            ->findOrFail(decryptIdIfEncrypted($id));
    }

    public function create(array $data): PembebananDosen
    {
        return DB::transaction(function () use ($data) {
            $entity = PembebananDosen::create($data);

            logActivity('perkuliahan', 'Menambah pembebanan dosen', $entity);

            return $entity;
        });
    }

    public function update(string|int $id, array $data): PembebananDosen
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);
            $entity->update($data);

            logActivity('perkuliahan', 'Memperbarui pembebanan dosen', $entity);

            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);

            logActivity('perkuliahan', 'Menghapus pembebanan dosen', null);

            return $entity->delete();
        });
    }

    public function getPeranOptions(): array
    {
        return [
            'pengampu' => 'Pengampu',
            'asisten' => 'Asisten',
            'koordinator' => 'Koordinator',
        ];
    }
}
