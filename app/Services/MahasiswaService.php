<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\Mahasiswa;
use Modules\Akademik\Models\Krs;
use Modules\Akademik\Models\Nilai;
use Modules\Akademik\Models\PeriodeAkademik;
use Modules\Kurikulum\Services\KurikulumService;
use Modules\Kurikulum\Models\SettingProdi;
use Modules\Kurikulum\Models\KurikulumMataKuliah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Akademik\Models\Biodata;
use Modules\Akademik\Models\Cekal;
use Modules\Akademik\Models\Cuti;
use Modules\Akademik\Services\NilaiService;

class MahasiswaService
{
    public function __construct(
        protected KurikulumService $kurikulumService,
        protected NilaiService $nilaiService,
    ) {}

    public function getBaseQuery(): Builder
    {
        return Mahasiswa::query()->select(['mahasiswa_id', 'tenant_id', 'nim', 'nama', 'email', 'prodi_id', 'angkatan', 'kurikulum_kode', 'status', 'jenis_masuk', 'semester_masuk', 'sks_diakui_awal', 'created_at', 'updated_at'])->orderBy('nim');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();
        $search = is_array($filters['search'] ?? null) ? ($filters['search']['value'] ?? '') : ($filters['search'] ?? '');
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")->orWhere('nama', 'like', "%{$search}%");
            });
        }
        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['angkatan']) && $filters['angkatan'] !== 'all') {
            $query->where('angkatan', $filters['angkatan']);
        }
        if (! empty($filters['prodi_id']) && $filters['prodi_id'] !== 'all') {
            $query->where('prodi_id', decryptIdIfEncrypted($filters['prodi_id']));
        }

        return $query;
    }

    public function getAll(): Collection
    {
        return Mahasiswa::orderBy('nim')->get();
    }

    public function findById(string|int $id): Mahasiswa
    {
        return Mahasiswa::with(['biodata'])->findOrFail(decryptIdIfEncrypted($id));
    }

    public function isCekal(string|int $mahasiswaId): bool
    {
        $id = is_int($mahasiswaId) ? $mahasiswaId : decryptIdIfEncrypted($mahasiswaId);

        return Cekal::where('mahasiswa_id', $id)->where('is_aktif', true)->exists();
    }

    /**
     * Cek apakah mahasiswa sedang cuti aktif (status disetujui) pada periode tsb.
     */
    public function isCutiAktif(string|int $mahasiswaId, ?int $periodeAkademikId = null): bool
    {
        $id = is_int($mahasiswaId) ? $mahasiswaId : decryptIdIfEncrypted($mahasiswaId);

        return Cuti::where('mahasiswa_id', $id)
            ->where('status', 'disetujui')
            ->when($periodeAkademikId, fn ($q) => $q->where('periode_akademik_id', $periodeAkademikId))
            ->exists();
    }

    /**
     * INT-01: Defense-in-depth guard untuk NimGeneratorService.
     * Cek apakah NIM sudah ada di tabel Mahasiswa (cross-module read-only).
     * Dipanggil Pmb saat generate NIM untuk tabrakan race condition.
     */
    public function nimExists(string $nim): bool
    {
        return Mahasiswa::where('nim', $nim)->exists();
    }

    public function create(array $data): Mahasiswa
    {
        return DB::transaction(function () use ($data) {
            $entity = Mahasiswa::create($data);
            logActivity('mahasiswa', sprintf('Menambah mahasiswa: %s - %s', $entity->nim, $entity->nama), $entity);

            return $entity;
        });
    }

    public function update(string|int $id, array $data): Mahasiswa
    {
        return DB::transaction(function () use ($id, $data) {
            $entity = $this->findById($id);
            $entity->update($data);
            logActivity('mahasiswa', sprintf('Memperbarui mahasiswa: %s - %s', $entity->nim, $entity->nama), $entity);

            return $entity;
        });
    }

    public function delete(string|int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $entity = $this->findById($id);
            logActivity('mahasiswa', sprintf('Menghapus mahasiswa: %s - %s', $entity->nim, $entity->nama), null);

            return $entity->delete();
        });
    }

    /**
     * Sinkronisasi data mahasiswa baru dari PMB (legacy — prefer createFromPmb).
     * Caller MUST pass data array from PMB's PendaftaranService::getSyncData() — never query PMB models directly.
     */
    public function syncFromPmb(array $syncData, string $nim, int $prodiId, int $angkatan): Mahasiswa
    {
        return DB::transaction(function () use ($syncData, $nim, $prodiId, $angkatan) {
            $pendaftaran = $syncData['pendaftaran'];
            $camaba = $syncData['camaba'];
            $kurikulumKode = $this->kurikulumService->getKodeKurikulumBinding($prodiId, $angkatan);

            // Create or Update Mahasiswa
            $mahasiswa = Mahasiswa::updateOrCreate(
                ['nim' => $nim],
                [
                    'tenant_id' => $pendaftaran['tenant_id'],
                    'user_id' => $pendaftaran['user_id'],
                    'pmb_pendaftar_id' => $pendaftaran['pendaftaran_id'] ?? $pendaftaran['pmb_pendaftar_id'] ?? null,
                    'nama' => $camaba['nama_lengkap'],
                    'email' => $camaba['email'],
                    'no_hp' => $camaba['no_hp'] ?? null,
                    'prodi_id' => $prodiId,
                    'angkatan' => $angkatan,
                    'kurikulum_kode' => $kurikulumKode,
                    'status' => 'aktif',
                    'jenis_masuk' => 'reguler',
                    'semester_masuk' => 1,
                ]
            );

            // Create Biodata
            Biodata::updateOrCreate(
                ['mahasiswa_id' => $mahasiswa->mahasiswa_id],
                [
                    'tenant_id' => $mahasiswa->tenant_id,
                    'nik' => $camaba['nik'],
                    'tempat_lahir' => $camaba['tempat_lahir'],
                    'tgl_lahir' => $camaba['tanggal_lahir'],
                    'jenis_kelamin' => $camaba['jenis_kelamin'],
                    'agama' => $camaba['agama'],
                    'alamat' => $camaba['alamat'],
                ]
            );

            logActivity('mahasiswa', sprintf('Sinkronisasi MHS dari PMB: %s - %s', $mahasiswa->nim, $mahasiswa->nama), $mahasiswa);

            return $mahasiswa;
        });
    }

    /**
     * Create mahasiswa dari PublishMahasiswaService (new orchestrator).
     * Includes RiwayatStatus recording.
     */
    public function createFromPmb(array $payload): int
    {
        return DB::transaction(function () use ($payload) {
            $mahasiswa = Mahasiswa::updateOrCreate(
                ['nim' => $payload['nim']],
                [
                    'user_id' => $payload['user_id'] ?? null,
                    'nama' => $payload['nama'],
                    'prodi_id' => $payload['prodi_id'],
                    'angkatan' => $payload['angkatan'],
                    'kurikulum_kode' => $payload['kurikulum_kode'] ?? null,
                    'pmb_pendaftar_id' => $payload['pmb_pendaftar_id'] ?? null,
                    'status' => 'aktif',
                    'jenis_masuk' => $payload['jenis_masuk'] ?? 'reguler',
                    'semester_masuk' => 1,
                ]
            );

            // Record Riwayat Status
            app(RiwayatStatusService::class)->create([
                'mahasiswa_id' => $mahasiswa->mahasiswa_id,
                'status_lama' => null,
                'status_baru' => 'aktif',
                'alasan' => 'Dibuat dari Publish Mahasiswa PMB',
                'tgl_efektif' => now()->toDateString(),
                'diproses_oleh' => 'System (Publish Mahasiswa)',
            ]);

            logActivity('mahasiswa', sprintf('Create MHS dari PMB: %s - %s', $mahasiswa->nim, $mahasiswa->nama), $mahasiswa);

            // Auto-create StatusSemester untuk semester 1
            app(StatusSemesterService::class)->create([
                'mahasiswa_id'     => $mahasiswa->mahasiswa_id,
                'periode_akademik_id' => null, // akan diisi saat periode aktif
                'status'           => 'aktif',
                'semester_ke'      => 1,
            ]);

            return $mahasiswa->mahasiswa_id;
        });
    }

    /**
     * Resolve mahasiswa by authenticated user id.
     */
    public function getByUserId(int $userId): ?Mahasiswa
    {
        return Mahasiswa::where('user_id', $userId)->first();
    }

    /**
     * Find raw mahasiswa by (already decrypted) id without relation load.
     */
    public function findByIdRaw(int|string $id): ?Mahasiswa
    {
        return Mahasiswa::find(is_int($id) ? $id : decryptIdIfEncrypted($id));
    }

    /**
     * Distinct angkatan list (sorted) for filter dropdowns.
     */
    public function getAngkatans(): Collection
    {
        return Mahasiswa::distinct()->pluck('angkatan')->sort()->values();
    }

    /**
     * Lightweight select2 search by NIM / nama.
     */
    public function searchSelect2(string $term): Collection
    {
        $query = Mahasiswa::query()->limit(20);

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('nama', 'like', "%{$term}%")
                  ->orWhere('nim', 'like', "%{$term}%");
            });
        }

        return $query->get(['mahasiswa_id', 'nim', 'nama', 'angkatan'])
            ->map(function ($m) {
                return [
                    'id'   => encryptId($m->mahasiswa_id),
                    'text' => $m->nama . ' (' . ($m->nim ?? '-') . ') • Angkatan ' . ($m->angkatan ?? '-'),
                ];
            });
    }

    /**
     * Lightweight API search (aktif only) returning raw mahasiswa rows.
     */
    public function getApiSearch(string $q): Collection
    {
        $query = Mahasiswa::query()
            ->select(['mahasiswa_id', 'nim', 'nama'])
            ->where('status', 'aktif');

        if (! empty($q)) {
            $query->where(function ($qb) use ($q) {
                $qb->where('nama', 'like', "%{$q}%")
                  ->orWhere('nim', 'like', "%{$q}%");
            });
        }

        return $query->orderBy('nim')->take(20)->get();
    }

    /**
     * Query builder for the REST API index (mahasiswa list).
     */
    public function getApiIndexQuery(array $filters): Builder
    {
        $query = Mahasiswa::query()
            ->with('prodi:id,orgunit_id,name')
            ->select(['mahasiswa_id', 'nim', 'nama', 'angkatan', 'prodi_id', 'status']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['angkatan'])) {
            $query->where('angkatan', $filters['angkatan']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['prodi_id'])) {
            $query->where('prodi_id', $filters['prodi_id']);
        }

        return $query->orderBy('nim');
    }

    /**
     * Find mahasiswa with prodi relation for the REST API show endpoint.
     */
    public function findApi(int $id): ?Mahasiswa
    {
        return Mahasiswa::with('prodi:id,orgunit_id,name')->findOrFail($id);
    }

    /**
     * Aggregated data for the mahasiswa-facing dashboard.
     */
    public function getDashboardData(Mahasiswa $mahasiswa, ?PeriodeAkademik $periode): array
    {
        $periodeId = $periode?->periode_akademik_id;

        $krsAktif = $periode
            ? Krs::where('mahasiswa_id', $mahasiswa->mahasiswa_id)
                ->where('periode_akademik_id', $periodeId)
                ->first()
            : null;

        $sksDiambil = $krsAktif
            ? $krsAktif->details()->where('status', 'aktif')->count()
            : 0;

        $ipk = $this->nilaiService->hitungIpk($mahasiswa->mahasiswa_id);
        $ips = $periode
            ? $this->nilaiService->hitungIps($mahasiswa->mahasiswa_id, $periodeId)
            : 0;

        $sksLulus = Nilai::where('mahasiswa_id', $mahasiswa->mahasiswa_id)
            ->where('is_lulus', true)
            ->sum('sks');

        $cekalAktif = Cekal::where('mahasiswa_id', $mahasiswa->mahasiswa_id)
            ->where('is_aktif', true)
            ->first();

        $nilaiTerakhir = Nilai::with('mataKuliah')
            ->where('mahasiswa_id', $mahasiswa->mahasiswa_id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $cutiAktif = Cuti::where('mahasiswa_id', $mahasiswa->mahasiswa_id)
            ->where('status', 'pending')
            ->exists();

        $totalSksKurikulum = 0;
        $settingProdi = SettingProdi::where('prodi_id', $mahasiswa->prodi_id)
            ->where('is_aktif', true)
            ->first();
        if ($settingProdi && $settingProdi->kurikulum_id) {
            $totalSksKurikulum = KurikulumMataKuliah::where('kurikulum_id', $settingProdi->kurikulum_id)
                ->sum('sks');
        }
        $persentaseKelulusan = $totalSksKurikulum > 0
            ? round(($sksLulus / $totalSksKurikulum) * 100, 1)
            : 0;

        return compact(
            'krsAktif', 'sksDiambil', 'ipk', 'ips', 'sksLulus',
            'totalSksKurikulum', 'persentaseKelulusan', 'cekalAktif',
            'nilaiTerakhir', 'cutiAktif'
        );
    }

    /**
     * Opsi mahasiswa (dropdown) — orderBy nama.
     */
    public function getForSelect(): Collection
    {
        return Mahasiswa::orderBy('nama')->get();
    }
}
