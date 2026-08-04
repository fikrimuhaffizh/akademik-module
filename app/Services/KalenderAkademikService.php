<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\KalenderAkademik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class KalenderAkademikService
{
    public function getBaseQuery(): Builder
    {
        return KalenderAkademik::query()->with(['periodeAkademik'])->select(['kalender_id', 'tenant_id', 'periode_akademik_id', 'nama_kegiatan', 'tgl_mulai', 'tgl_selesai', 'jenis', 'keterangan', 'created_at', 'updated_at'])->orderByDesc('tgl_mulai');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();
        if (! empty($filters['periode_akademik_id'])) {
            $query->where('periode_akademik_id', decryptIdIfEncrypted($filters['periode_akademik_id']));
        }
        return $query;
    }

    public function getAll(): Collection { return KalenderAkademik::with('periodeAkademik')->get(); }
    public function findById(string|int $id): KalenderAkademik { return KalenderAkademik::findOrFail(decryptIdIfEncrypted($id)); }

    public function create(array $data): KalenderAkademik
    {
        return DB::transaction(function () use ($data) {
            $entity = KalenderAkademik::create($data);
            logActivity('perkuliahan', sprintf('Menambah kalender: %s', $entity->nama_kegiatan), $entity);
            return $entity;
        });
    }

    public function update(string|int $id, array $data): KalenderAkademik
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);
            $entity->update($data);
            logActivity('perkuliahan', sprintf('Memperbarui kalender: %s', $entity->nama_kegiatan), $entity);
            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);
            logActivity('perkuliahan', sprintf('Menghapus kalender: %s', $entity->nama_kegiatan), null);
            return $entity->delete();
        });
    }

    /**
     * Event kalender jenis 'krs' untuk periode tertentu (terbaru).
     */
    public function getKrsEvent(int $periodeAkademikId): ?KalenderAkademik
    {
        return KalenderAkademik::where('periode_akademik_id', $periodeAkademikId)
            ->where('jenis', 'krs')
            ->orderByDesc('tgl_mulai')
            ->first();
    }
}
