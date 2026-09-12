<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\MahasiswaDraft;
use Modules\Akademik\Models\Mahasiswa;
use Modules\Akademik\Models\Biodata;
use Modules\Akademik\Models\RiwayatStatus;
use Modules\Akademik\Models\StatusSemester;
use Modules\Akademik\Services\MahasiswaService;
use Modules\Referensi\Services\SysRefService;
use Modules\Account\Models\Role;
use Modules\Account\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MahasiswaDraftService
{
    // NIM tidak lagi digenerate di sini — memakai nim_final bawaan PMB.

    public function __construct(
        protected SysRefService $sysRefService,
    ) {}

    /**
     * Angkatan list for draft filter dropdowns.
     * Sumber utama: master data Referensi (sys_refs, grup angkatan_mahasiswa);
     * digabung dengan angkatan yang sudah ada di akd_mahasiswa_draft.
     */
    public function getAngkatans(): SupportCollection
    {
        $master = $this->sysRefService
            ->getActiveByGrup('angkatan_mahasiswa')
            ->pluck('label')
            ->map(fn ($label) => (string) $label);

        $terpakai = MahasiswaDraft::query()
            ->distinct()
            ->orderBy('angkatan')
            ->pluck('angkatan')
            ->map(fn ($angkatan) => (string) $angkatan);

        return $master
            ->merge($terpakai)
            ->unique()
            ->sort()
            ->values();
    }

    public function getBaseQuery(): Builder
    {
        return MahasiswaDraft::query()
            ->select([
                'draft_id', 'tenant_id', 'pmb_pendaftar_id', 'nim', 'nama', 'email', 'no_hp',
                'prodi_id', 'angkatan', 'kurikulum_kode', 'jenis_masuk', 'sistem_kuliah',
                'status_draft', 'submitted_at', 'created_at', 'updated_at',
            ])
            ->orderBy('created_at', 'desc');
    }

    public function getFilteredQuery(array $filters = []): Builder
    {
        $query = $this->getBaseQuery();

        $search = is_array($filters['search'] ?? null) ? ($filters['search']['value'] ?? '') : ($filters['search'] ?? '');
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status_draft']) && $filters['status_draft'] !== 'all') {
            $query->where('status_draft', $filters['status_draft']);
        }

        if (! empty($filters['prodi_id']) && $filters['prodi_id'] !== 'all') {
            $query->where('prodi_id', decryptIdIfEncrypted($filters['prodi_id']));
        }

        if (! empty($filters['angkatan']) && $filters['angkatan'] !== 'all') {
            $query->where('angkatan', $filters['angkatan']);
        }

        return $query;
    }

    public function findById(string|int $id): ?MahasiswaDraft
    {
        // Terima id mentah maupun ter-enkripsi. decryptIdIfEncrypted() bisa
        // mengembalikan string asli bila dekripsi gagal — fallback ke cari PK langsung.
        $decrypted = decryptIdIfEncrypted((string) $id);

        return MahasiswaDraft::with(['prodi'])->find(is_int($decrypted) ? $decrypted : $id);
    }

    /**
     * Set kurikulum untuk banyak draft sekaligus.
     *
     * mode 'manual': semua draft dipaksa ke kurikulum_kode yang dipilih.
     * mode 'auto'  : kurikulum di-resolve per draft via SettingProdi
     *                (kur_kurikulum.prodi_id = prodi draft, angkatan_list
     *                memuat angkatan draft) — kurikulum dan prodi berbagi
     *                kode orgunit yang sama, jadi binding selalu konsisten.
     */
    public function setKurikulumBulk(array $draftIds, string $mode, ?string $kurikulumKode = null): array
    {
        $updated = 0;
        $errors  = [];

        foreach ($draftIds as $id) {
            try {
                DB::transaction(function () use ($id, $mode, $kurikulumKode, &$updated, &$errors) {
                    $draft = MahasiswaDraft::findOrFail($id);

                    if ($mode === 'manual') {
                        $draft->update(['kurikulum_kode' => $kurikulumKode]);
                        $updated++;

                        return;
                    }

                    // Auto: resolve via SettingProdi (prodi + angkatan).
                    $kode = app(\Modules\Kurikulum\Services\SettingProdiService::class)
                        ->getKurikulumForAngkatan((int) $draft->prodi_id, (int) $draft->angkatan)
                        ?->kurikulum?->kode_kurikulum;

                    if (empty($kode)) {
                        $errors[] = "Draft {$draft->nim}: tidak ada binding kurikulum untuk prodi + angkatan {$draft->angkatan} di Setting Prodi";

                        return;
                    }

                    $draft->update(['kurikulum_kode' => $kode]);
                    $updated++;
                });
            } catch (\Throwable $e) {
                report($e);
                $errors[] = "Draft #{$id}: " . $e->getMessage();
            }
        }

        logActivity('akademik', sprintf('Set kurikulum massal: mode=%s, updated=%d, errors=%d', $mode, $updated, count($errors)));

        return ['updated' => $updated, 'errors' => $errors];
    }
    /**
     * Set status akhir draft (draft | submitted | batal).
     * Dipakai aksi "Set Status Akhir" di datatable draft.
     */
    public function setStatusAkhir(int $id, string $status): MahasiswaDraft
    {
        return DB::transaction(function () use ($id, $status) {
            $draft = MahasiswaDraft::findOrFail($id);
            $draft->update(['status_draft' => $status]);

            if ($status === 'submitted') {
                $draft->update(['submitted_at' => now()]);
            }

            logActivity('akademik', sprintf('Set status akhir draft mahasiswa %s: %s', $draft->nim, $status), $draft);

            return $draft;
        });
    }
    public function update(int $id, array $data): MahasiswaDraft
    {
        return DB::transaction(function () use ($id, $data) {
            $draft = MahasiswaDraft::findOrFail($id);
            $draft->update($data);
            logActivity('akademik', sprintf('Memperbarui draft mahasiswa: %s', $draft->nama), $draft);
            return $draft;
        });
    }

    /**
     * Sync pendaftar baru dari PMB ke draft table.
     * Idempotent — hanya insert yang belum ada di draft.
     * Satu server = langsung in-process; beda server = HTTP.
     */
    public function syncFromPmb(?int $periodeId = null): array
    {
        if (use_local('pmb', \Modules\Pmb\Services\PendaftaranService::class)) {
            $pendaftarans = app(\Modules\Pmb\Services\PendaftaranService::class)
                ->getMahasiswaBaru(array_filter(['periode_id' => $periodeId]));

            return $this->importPendaftarans($pendaftarans);
        }

        $baseUrl = config('akademik.pmb_base_url');
        $token = config('akademik.pmb_token');

        if (empty($baseUrl) || empty($token)) {
            return ['synced' => 0, 'skipped' => 0, 'errors' => ['PMBAPI base_url or token not configured']];
        }

        try {
            $json = service_api('pmb', 'GET', '/api/v1/pmb/mahasiswa-baru', array_filter([
                'periode_id' => $periodeId,
            ]), ['base_url' => $baseUrl, 'token' => $token]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $status = $e->response?->status() ?? 'unknown';

            return ['synced' => 0, 'skipped' => 0, 'errors' => ['PMBAPI returned status '.$status]];
        } catch (\Throwable $e) {
            return ['synced' => 0, 'skipped' => 0, 'errors' => [$e->getMessage()]];
        }

        return $this->importPendaftarans($json['data'] ?? []);
    }

    /**
     * Import baris mahasiswa-baru PMB ke draft (dipakai jalur HTTP dan in-process).
     */
    private function importPendaftarans(array $pendaftarans): array
    {
        $synced = 0;
        $skipped = 0;
        $errors = [];

        foreach ($pendaftarans as $pendaftaran) {
            try {
                $pmbId = $pendaftaran['pendaftaran_id'] ?? $pendaftaran['pmb_pendaftar_id'] ?? null;
                if (! $pmbId) {
                    $errors[] = 'Missing pmb_pendaftar_id in record';
                    continue;
                }

                $tenantId = $pendaftaran['tenant_id'] ?? sys_tenant_id(1);

                // Idempotent check
                $exists = MahasiswaDraft::where('tenant_id', $tenantId)
                    ->where('pmb_pendaftar_id', $pmbId)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $kandidat = $pendaftaran['kandidat'] ?? [];
                // prodi_id Akademik = HR orgunit (dipetakan dari prodi PMB).
                // Tanpa mapping, submit dilarang agar tidak salah prodi.
                $prodiId = $pendaftaran['hr_orgunit_id'] ?? null;
                if (empty($prodiId)) {
                    $errors[] = "Pendaftaran {$pmbId}: prodi belum dipetakan ke struktur HR (hr_orgunit_id kosong)";

                    continue;
                }
                $angkatan = $pendaftaran['angkatan'] ?? (int) now()->format('Y');

                // NIM hanya dari PMB (nim_final) — Akademik tidak generate sendiri.
                // Baris tanpa NIM dilewati: daftar ulangnya belum selesai di PMB.
                $nim = $pendaftaran['nim_final'] ?? null;
                if (empty($nim)) {
                    $errors[] = "Pendaftaran {$pmbId}: belum punya NIM (daftar ulang belum selesai di PMB)";

                    continue;
                }


                // Auto-resolve kurikulum via SettingProdi (prodi + angkatan).
                $kurikulumKode = null;
                try {
                    $kurikulumKode = app(\Modules\Kurikulum\Services\SettingProdiService::class)
                        ->getKurikulumForAngkatan((int) $prodiId, (int) $angkatan)
                        ?->kurikulum?->kode_kurikulum;
                } catch (\Throwable $e) {
                    report($e);
                }

                // Build full snapshot from PMB data
                $snapshot = [
                    'kandidat' => $kandidat,
                    'pendaftaran' => $pendaftaran,
                ];

                MahasiswaDraft::create([
                    'tenant_id' => $tenantId,
                    'pmb_pendaftar_id' => $pmbId,
                    'nim' => $nim,
                    'nama' => $kandidat['nama_lengkap'] ?? $pendaftaran['nama'] ?? '-',
                    'email' => $kandidat['email'] ?? null,
                    'no_hp' => $kandidat['no_hp'] ?? null,
                    'prodi_id' => $prodiId,
                    'angkatan' => $angkatan,
                    'kurikulum_kode' => $kurikulumKode,
                    'jenis_masuk' => $pendaftaran['jenis_masuk'] ?? null,
                    'sistem_kuliah' => $pendaftaran['sistem_kuliah'] ?? null,
                    'status_draft' => 'draft',
                    'snapshot_json' => $snapshot,
                ]);

                $synced++;
            } catch (\Throwable $e) {
                report($e);
                $errors[] = $e->getMessage();
            }
        }

        logActivity('akademik', sprintf('Sync PMB→Draft: synced=%d, skipped=%d, errors=%d', $synced, $skipped, count($errors)));

        return ['synced' => $synced, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Submit draft menjadi mahasiswa aktif.
     * Creates: User, Mahasiswa, Biodata, RiwayatStatus, StatusSemester.
     * Updates PMB with nim_final + finalized_at.
     */
    public function submit(array $draftIds): array
    {
        $submitted = 0;
        $errors = [];

        foreach ($draftIds as $draftId) {
            try {
                DB::transaction(function () use ($draftId, &$submitted) {
                    $draft = MahasiswaDraft::where('status_draft', 'draft')->findOrFail($draftId);

                    $nim = $draft->nim;
                    $snapshot = $draft->snapshot_json ?? [];
                    $kandidat = $snapshot['kandidat'] ?? [];
                    $pendaftaranSnap = $snapshot['pendaftaran'] ?? [];

                    // Validasi preview: email + kurikulum + NIM unik.
                    $email = trim((string) ($draft->email ?? ''));
                    if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new \RuntimeException("Draft {$draft->draft_id}: email belum diisi / tidak valid, perbaiki di preview.");
                    }
                    if (User::where('email', $email)->exists()) {
                        throw new \RuntimeException("Draft {$draft->draft_id}: email {$email} sudah dipakai akun lain.");
                    }
                    if (empty($draft->kurikulum_kode)) {
                        throw new \RuntimeException("Draft {$draft->draft_id}: kurikulum belum ditentukan, pilih di preview.");
                    }
                    if (empty($draft->prodi_id)) {
                        throw new \RuntimeException("Draft {$draft->draft_id}: prodi belum ditentukan.");
                    }
                    if (app(MahasiswaService::class)->nimExists($nim)) {
                        throw new \RuntimeException("Draft {$draft->draft_id}: NIM {$nim} sudah dipakai mahasiswa lain.");
                    }

                    // 1. Create User baru (password = NIM dibalik) + role Mahasiswa.
                    $role = Role::firstOrCreate([
                        'tenant_id' => $draft->tenant_id,
                        'name' => 'Mahasiswa',
                        'guard_name' => 'web',
                    ]);
                    $user = User::create([
                        'tenant_id' => $draft->tenant_id,
                        'name' => $draft->nama,
                        'email' => $email,
                        'password' => Hash::make(strrev($nim)),
                        'is_active' => true,
                    ]);
                    if (function_exists('setPermissionsTeamId')) {
                        setPermissionsTeamId((int) $draft->tenant_id);
                    }
                    $user->assignRole($role);
                    // User di koneksi sys_core (transaksi utama tak mencakupnya):
                    // bila langkah berikut gagal, hapus user agar tidak yatim.
                    try {

                    // 2. Create Mahasiswa via pintu tunggal (metadata: no_pendaftaran + tagihan DU).
                    $mahasiswaId = app(MahasiswaService::class)->createFromSyncPayload([
                        'nim' => $nim,
                        'user_id' => $user->id,
                        'nama' => $draft->nama,
                        'email' => $email,
                        'no_hp' => $draft->no_hp,
                        'prodi_id' => $draft->prodi_id,
                        'angkatan' => $draft->angkatan,
                        'kurikulum_kode' => $draft->kurikulum_kode,
                        'jenis_masuk' => $draft->jenis_masuk,
                        'sistem_kuliah' => $draft->sistem_kuliah,
                        'pmb_pendaftar_id' => $draft->pmb_pendaftar_id,
                        'no_pendaftaran' => $pendaftaranSnap['no_pendaftaran'] ?? null,
                        'tagihan_du' => [
                            'tagihan_id' => $pendaftaranSnap['tagihan_daftar_ulang_id'] ?? null,
                            'lunas' => true,
                        ],
                    ]);
                    $mahasiswa = Mahasiswa::findOrFail($mahasiswaId);

                    // 3. Create Biodata penuh dari snapshot PMB.
                    Biodata::updateOrCreate(
                        ['mahasiswa_id' => $mahasiswa->mahasiswa_id],
                        [
                            'tenant_id' => $draft->tenant_id,
                            'nik' => $kandidat['nik'] ?? null,
                            'tempat_lahir' => $kandidat['tempat_lahir'] ?? null,
                            'tgl_lahir' => $kandidat['tanggal_lahir'] ?? null,
                            'jenis_kelamin' => $kandidat['jenis_kelamin'] ?? null,
                            'agama' => $kandidat['agama'] ?? null,
                            'kewarganegaraan' => $kandidat['kewarganegaraan'] ?? null,
                            'suku' => $kandidat['suku'] ?? null,
                            'alamat' => $kandidat['alamat_lengkap'] ?? $kandidat['alamat'] ?? null,
                            'provinsi_kode' => $kandidat['provinsi_kode'] ?? null,
                            'kabupaten_kode' => $kandidat['kabupaten_kode'] ?? null,
                            'kecamatan_kode' => $kandidat['kecamatan_kode'] ?? null,
                            'kelurahan_kode' => $kandidat['kelurahan_kode'] ?? null,
                            'kode_pos' => $kandidat['kode_pos'] ?? null,
                            'nama_ayah' => $kandidat['nama_ayah'] ?? null,
                            'nik_ayah' => $kandidat['nik_ayah'] ?? null,
                            'tgl_lahir_ayah' => $kandidat['tgl_lahir_ayah'] ?? null,
                            'pendidikan_ayah' => $kandidat['pendidikan_ayah'] ?? null,
                            'pekerjaan_ayah' => $kandidat['pekerjaan_ayah'] ?? null,
                            'penghasilan_ayah' => $kandidat['penghasilan_ayah'] ?? null,
                            'nama_ibu' => $kandidat['nama_ibu'] ?? null,
                            'nik_ibu' => $kandidat['nik_ibu'] ?? null,
                            'tgl_lahir_ibu' => $kandidat['tgl_lahir_ibu'] ?? null,
                            'pendidikan_ibu' => $kandidat['pendidikan_ibu'] ?? null,
                            'pekerjaan_ibu' => $kandidat['pekerjaan_ibu'] ?? null,
                            'penghasilan_ibu' => $kandidat['penghasilan_ibu'] ?? null,
                            'nama_wali' => $kandidat['nama_wali'] ?? null,
                            'nik_wali' => $kandidat['nik_wali'] ?? null,
                            'tgl_lahir_wali' => $kandidat['tgl_lahir_wali'] ?? null,
                            'pendidikan_wali' => $kandidat['pendidikan_wali'] ?? null,
                            'pekerjaan_wali' => $kandidat['pekerjaan_wali'] ?? null,
                            'penghasilan_wali' => $kandidat['penghasilan_wali'] ?? null,
                        ]
                    );



                    // 4-5. Riwayat + semester dibuat di createFromSyncPayload (pintu tunggal).

                    } catch (\Throwable $e) {
                        $user->forceDelete();

                        throw $e;
                    }

                    // 6. Update draft: status_draft='submitted', submitted_at=now()
                    $draft->update([
                        'status_draft' => 'submitted',
                        'submitted_at' => now(),
                    ]);

                    // 7. Kabari PMB: catat nim_final + finalized_at.
                    // Satu server = langsung in-process; beda server = HTTP.
                    try {
                        if (use_local('pmb', \Modules\Pmb\Services\PendaftaranService::class)) {
                            app(\Modules\Pmb\Services\PendaftaranService::class)->applyFinalize(
                                (int) $draft->pmb_pendaftar_id,
                                $nim,
                                now()->toIso8601String(),
                            );
                        } else {
                            $pmbBase = config('akademik.pmb_base_url');
                            $pmbToken = config('akademik.pmb_token');
                            if ($pmbBase && $pmbToken) {
                                service_api('pmb', 'POST', "/api/v1/pmb/pendaftaran/{$draft->pmb_pendaftar_id}/finalize", [
                                    'nim_final' => $nim,
                                    'finalized_at' => now()->toIso8601String(),
                                ], ['base_url' => $pmbBase, 'token' => $pmbToken]);
                            }
                        }
                    } catch (\Throwable $e) {
                        report($e);
                    }

                    $submitted++;
                });
            } catch (\Throwable $e) {
                report($e);
                $errors[] = ['draft_id' => $draftId, 'message' => $e->getMessage()];
            }
        }

        logActivity('akademik', sprintf('Submit Draft→MHS: submitted=%d, errors=%d', $submitted, count($errors)));

        return ['submitted' => $submitted, 'errors' => $errors];
    }
}
