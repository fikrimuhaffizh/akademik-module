<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\MahasiswaDraft;
use Modules\Akademik\Models\Mahasiswa;
use Modules\Akademik\Models\Biodata;
use Modules\Akademik\Models\RiwayatStatus;
use Modules\Akademik\Models\StatusSemester;
use Modules\Account\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MahasiswaDraftService
{
    // NIM tidak lagi digenerate di sini — memakai nim_final bawaan PMB.

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

    public function findById(int $id): ?MahasiswaDraft
    {
        return MahasiswaDraft::with(['prodi'])->find($id);
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

                $camaba = $pendaftaran['camaba'] ?? [];
                $prodiId = $pendaftaran['prodi_id'] ?? null;
                $angkatan = $pendaftaran['angkatan'] ?? (int) now()->format('Y');

                // NIM hanya dari PMB (nim_final) — Akademik tidak generate sendiri.
                // Baris tanpa NIM dilewati: daftar ulangnya belum selesai di PMB.
                $nim = $pendaftaran['nim_final'] ?? null;
                if (empty($nim)) {
                    $errors[] = "Pendaftaran {$pmbId}: belum punya NIM (daftar ulang belum selesai di PMB)";

                    continue;
                }

                // Auto-resolve kurikulum — langsung in-process bila satu server.
                $kurikulumKode = null;
                try {
                    if (class_exists(\Modules\Kurikulum\Services\KurikulumService::class)) {
                        $kurikulumKode = app(\Modules\Kurikulum\Services\KurikulumService::class)
                            ->getKurikulumByProdiAngkatan($prodiId, $angkatan)?->kode_kurikulum;
                    } else {
                        $kurBase = config('pmb.kurikulum_base_url', '');
                        $kurToken = config('pmb.kurikulum_token', '');
                        if ($kurBase && $kurToken) {
                            $kurJson = service_api('kurikulum', 'GET', '/api/v1/kur/kurikulum/resolve', [
                                'prodi_id' => $prodiId,
                                'angkatan' => $angkatan,
                            ], ['base_url' => $kurBase, 'token' => $kurToken]);
                            $kurikulumKode = $kurJson['data']['kode_kurikulum'] ?? null;
                        }
                    }
                } catch (\Throwable $e) {
                    report($e);
                }

                // Build full snapshot from PMB data
                $snapshot = [
                    'camaba' => $camaba,
                    'pendaftaran' => $pendaftaran,
                ];

                MahasiswaDraft::create([
                    'tenant_id' => $tenantId,
                    'pmb_pendaftar_id' => $pmbId,
                    'nim' => $nim,
                    'nama' => $camaba['nama_lengkap'] ?? $pendaftaran['nama'] ?? '-',
                    'email' => $camaba['email'] ?? null,
                    'no_hp' => $camaba['no_hp'] ?? null,
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
                    $camaba = $snapshot['camaba'] ?? [];

                    // 1. Create User (username=NIM, password=NIM, role=mahasiswa)
                    $user = User::create([
                        'tenant_id' => $draft->tenant_id,
                        'name' => $draft->nama,
                        'email' => $draft->email ?? $nim . '@draft.local',
                        'password' => Hash::make($nim),
                        'is_active' => true,
                    ]);
                    $user->assignRole('mahasiswa');

                    // 2. Create Mahasiswa (status=aktif)
                    $mahasiswa = Mahasiswa::create([
                        'tenant_id' => $draft->tenant_id,
                        'nim' => $nim,
                        'nama' => $draft->nama,
                        'email' => $draft->email,
                        'no_hp' => $draft->no_hp,
                        'prodi_id' => $draft->prodi_id,
                        'angkatan' => $draft->angkatan,
                        'kurikulum_kode' => $draft->kurikulum_kode,
                        'status' => 'aktif',
                        'jenis_masuk' => $draft->jenis_masuk,
                        'sistem_kuliah' => $draft->sistem_kuliah,
                        'semester_masuk' => 1,
                        'pmb_pendaftar_id' => $draft->pmb_pendaftar_id,
                        'user_id' => $user->id,
                    ]);

                    // 3. Create Biodata (extract from snapshot_json)
                    Biodata::create([
                        'tenant_id' => $draft->tenant_id,
                        'mahasiswa_id' => $mahasiswa->mahasiswa_id,
                        'nik' => $camaba['nik'] ?? null,
                        'tempat_lahir' => $camaba['tempat_lahir'] ?? null,
                        'tgl_lahir' => $camaba['tanggal_lahir'] ?? null,
                        'jenis_kelamin' => $camaba['jenis_kelamin'] ?? null,
                        'agama' => $camaba['agama'] ?? null,
                        'alamat' => $camaba['alamat'] ?? null,
                        'kota' => $camaba['kota'] ?? null,
                        'provinsi' => $camaba['provinsi'] ?? null,
                        'kode_pos' => $camaba['kode_pos'] ?? null,
                        'nama_ayah' => $camaba['nama_ayah'] ?? null,
                        'pekerjaan_ayah' => $camaba['pekerjaan_ayah'] ?? null,
                        'nama_ibu' => $camaba['nama_ibu'] ?? null,
                        'pekerjaan_ibu' => $camaba['pekerjaan_ibu'] ?? null,
                    ]);

                    // 4. Create RiwayatStatus (null → aktif)
                    RiwayatStatus::create([
                        'tenant_id' => $draft->tenant_id,
                        'mahasiswa_id' => $mahasiswa->mahasiswa_id,
                        'status_lama' => null,
                        'status_baru' => 'aktif',
                        'alasan' => 'Dibuat dari Publish Draft PMB',
                        'tgl_efektif' => now()->toDateString(),
                        'diproses_oleh' => 'System (Publish Draft)',
                    ]);

                    // 5. Create StatusSemester (semester 1, aktif)
                    StatusSemester::create([
                        'tenant_id' => $draft->tenant_id,
                        'mahasiswa_id' => $mahasiswa->mahasiswa_id,
                        'periode_akademik_id' => null,
                        'status' => 'aktif',
                        'semester_ke' => 1,
                    ]);

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
