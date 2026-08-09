<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\Mahasiswa;
use Modules\Kurikulum\Services\KurikulumService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Akademik\Models\Biodata;
use Modules\Akademik\Models\Cekal;
use Modules\Akademik\Models\Cuti;

class MahasiswaService
{
    public function __construct(protected KurikulumService $kurikulumService) {}

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
     * Opsi mahasiswa (dropdown) — orderBy nama.
     */
    public function getForSelect(): Collection
    {
        return Mahasiswa::orderBy('nama')->get();
    }
}
