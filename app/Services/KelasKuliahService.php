<?php

namespace Modules\Akademik\Services;

use Illuminate\Support\Facades\DB;
use Modules\HrCore\Services\PegawaiService;
use Modules\Akademik\Models\KelasKuliah;
use Illuminate\Database\Eloquent\Builder;
use Modules\Akademik\Models\PembebananDosen;
use Modules\Akademik\Models\PenawaranMataKuliah;
use Modules\Akademik\Models\RuangKuliah;
use Modules\Referensi\Models\SysRef;

class KelasKuliahService
{
    public function __construct(
        protected PegawaiService $pegawaiService
    ) {}

    public function getCount(): int
    {
        return KelasKuliah::count();
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getDataQuery();

        // Global search — support string maupun format DataTables search[value]
        $searchValue = $filters['search'] ?? null;
        if (is_array($searchValue)) {
            $searchValue = $searchValue['value'] ?? null;
        }
        if ($searchValue !== null && $searchValue !== '') {
            $search = (string) $searchValue;
            $query->where(function ($q) use ($search) {
                $q->where('nama_kelas', 'like', "%{$search}%")
                  ->orWhere('sistem_kuliah', 'like', "%{$search}%")
                  ->orWhereHas('penawaran.kurikulumMataKuliah.mataKuliah', function ($mq) use ($search) {
                      $mq->where('kode', 'like', "%{$search}%")
                         ->orWhere('nama', 'like', "%{$search}%");
                  });
            });
        }

        return $query;
    }

    public function getDataQuery()
    {
        return KelasKuliah::with([
                'penawaran.kurikulumMataKuliah.mataKuliah',
                'refKelas',
                'pembebananDosens',
                'jadwalKuliahs',
            ])
            ->select([
                'kelas_id', 'tenant_id', 'penawaran_id', 'ref_kelas_id',
                'nama_kelas', 'kapasitas', 'sistem_kuliah', 'is_aktif',
                'created_at', 'updated_at',
            ])
            ->orderByDesc('created_at');
    }

    /**
     * Opsi kelas kuliah aktif untuk select (create/edit jadwal & pembebanan).
     */
    public function getAktifOptions()
    {
        return KelasKuliah::where('is_aktif', true)->get(['kelas_id', 'nama_kelas']);
    }

    /**
     * Data untuk form create/edit (select options).
     */
    public function getFormData(): array
    {
        return [
            'penawarans' => PenawaranMataKuliah::with('kurikulumMataKuliah.mataKuliah')
                ->where('is_aktif', true)->get(),
            'refKelases' => SysRef::byGrup('kelas_perkuliahan')->aktif()->terurut()->get(),
            'ruangKuliahs' => RuangKuliah::where('is_aktif', true)
                ->orderBy('kode')->get(['ruang_id', 'kode', 'nama', 'kapasitas']),
            'pegawais' => $this->pegawaiService->getActiveForSelect(),
        ];
    }

    /**
     * Buat kelas kuliah + pembebanan dosen + jadwal dalam 1 transaksi.
     */
    public function createWithRelations(array $data, array $pembebanan, array $jadwals): KelasKuliah
    {
        return DB::transaction(function () use ($data, $pembebanan, $jadwals) {
            $kelas = KelasKuliah::create($data);

            foreach ($pembebanan as $p) {
                $kelas->pembebananDosens()->create([
                    'tenant_id' => $kelas->tenant_id,
                    'pegawai_id' => $p['pegawai_id'],
                    'peran' => $p['peran'],
                ]);
            }

            foreach ($jadwals as $j) {
                $kelas->jadwalKuliahs()->create(array_merge($j, [
                    'tenant_id' => $kelas->tenant_id,
                ]));
            }

            return $kelas;
        });
    }

    /**
     * Cek apakah ada pembebanan dosen bentrok (kelas lain) untuk konflik jadwal.
     */
    public function existsDosenConflict(?int $ignoreKelasId, array $pegawaiIds): bool
    {
        if (empty($pegawaiIds)) {
            return false;
        }

        return PembebananDosen::where('kelas_id', $ignoreKelasId ?? 0)
            ->whereIn('pegawai_id', $pegawaiIds)
            ->exists();
    }

    /**
     * Cari kelas kuliah + relasi detail untuk halaman rekap EDOM.
     */
    public function findWithDetail(int $kelasId): ?KelasKuliah
    {
        return KelasKuliah::with('penawaran.kurikulumMataKuliah.mataKuliah')
            ->findOrFail($kelasId);
    }

    /**
     * Kelas kuliah by koleksi id (untuk list EDOM mahasiswa).
     */
    public function getByIds(array $kelasIds): EloquentCollection
    {
        if (empty($kelasIds)) {
            return new EloquentCollection();
        }

        return KelasKuliah::with([
                'penawaran.kurikulumMataKuliah.mataKuliah',
                'pembebananDosens.pegawai',
            ])
            ->whereIn('kelas_id', $kelasIds)
            ->get();
    }
}
