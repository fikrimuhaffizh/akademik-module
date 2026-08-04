<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\BatasSks;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BatasSksService
{
    public function getBaseQuery(): Builder
    {
        return BatasSks::query()->orderBy('ipk_min');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();

        if (! empty($filters['periode_akademik_id'])) {
            $query->where('periode_akademik_id', decryptIdIfEncrypted($filters['periode_akademik_id']));
        }

        return $query;
    }

    public function getAll(): Collection
    {
        return BatasSks::orderBy('ipk_min')->get();
    }

    public function findById(string|int $id): BatasSks
    {
        return BatasSks::findOrFail(decryptIdIfEncrypted($id));
    }

    public function getBatasByIpk(float $ipk, int $periodeAkademikId): int
    {
        return (int) (BatasSks::where('periode_akademik_id', $periodeAkademikId)
            ->where('ipk_min', '<=', $ipk)
            ->where('ipk_max', '>=', $ipk)
            ->orderByDesc('ipk_min')
            ->value('max_sks') ?? 24);
    }

    public function create(array $data): BatasSks
    {
        return DB::transaction(fn () => BatasSks::create($data));
    }

    public function update(string|int $id, array $data): BatasSks
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);
            $entity->update($data);

            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(fn () => $this->findById($id)->delete());
    }
}
