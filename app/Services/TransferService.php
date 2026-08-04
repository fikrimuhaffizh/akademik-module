<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\Transfer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransferService
{
    public function getBaseQuery(): Builder
    {
        return Transfer::query()->orderByDesc('created_at');
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
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query;
    }

    public function getAll(): Collection
    {
        return Transfer::orderByDesc('created_at')->get();
    }

    public function findById(string|int $id): Transfer
    {
        return Transfer::findOrFail(decryptIdIfEncrypted($id));
    }

    public function create(array $data): Transfer
    {
        return DB::transaction(function () use ($data) {
            $entity = Transfer::create($data);
            logActivity('mahasiswa', sprintf('Menambah transfer mahasiswa ID: %d, jenis: %s', $entity->mahasiswa_id, $entity->jenis), $entity);
            return $entity;
        });
    }

    public function update(string|int $id, array $data): Transfer
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);
            $entity->update($data);
            logActivity('mahasiswa', 'Memperbarui transfer mahasiswa', $entity);
            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);
            logActivity('mahasiswa', 'Menghapus transfer mahasiswa', null);
            return $entity->delete();
        });
    }

    // ═══════════════════════════════════════════════════════════
    //  APPROVAL WORKFLOW (M-06)
    // ═══════════════════════════════════════════════════════════

    /**
     * Setujui transfer mahasiswa.
     */
    public function approve(string|int $id): Transfer
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);

            if ($entity->status !== 'diajukan') {
                throw new RuntimeException('Hanya transfer berstatus diajukan yang dapat disetujui.');
            }

            $entity->update([
                'status' => 'disetujui',
            ]);

            logActivity('mahasiswa', sprintf('Menyetujui transfer mahasiswa: ID %d, jenis: %s', $entity->mahasiswa_id, $entity->jenis), $entity);

            return $entity;
        });
    }

    /**
     * Tolak transfer mahasiswa.
     */
    public function reject(string|int $id, string $alasan): Transfer
    {
        return DB::transaction(function () use ($id, $alasan) {
            $entity = $this->findById($id);

            if ($entity->status !== 'diajukan') {
                throw new RuntimeException('Hanya transfer berstatus diajukan yang dapat ditolak.');
            }

            $entity->update([
                'status' => 'ditolak',
            ]);

            logActivity('mahasiswa', sprintf('Menolak transfer mahasiswa: ID %d - %s', $entity->mahasiswa_id, $alasan), $entity);

            return $entity;
        });
    }

    /**
     * Dapatkan opsi status transfer.
     */
    public function getStatusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'diajukan' => 'Diajukan',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
        ];
    }
}
