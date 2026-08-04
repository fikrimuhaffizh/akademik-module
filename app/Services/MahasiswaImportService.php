<?php
namespace Modules\Akademik\Services;

use Modules\Sys\Services\ImportService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class MahasiswaImportService
{
    public function __construct(
        protected ImportService $importService
    ) {}

    public function createDraftFromUpload($file): string
    {
        set_time_limit(300);
        [$rows, , $headers] = $this->importService->readRows($file);

        return $this->importService->createBatch(
            "akmhs",
            "mahasiswa",
            $file->getClientOriginalName(),
            $headers ?: $this->defaultImportHeaders(),
            [],
            $this->validateImportRows($rows)
        );
    }

    public function reviewData(string $batchId): array
    {
        return $this->importService->reviewData($batchId, "akmhs", "mahasiswa", $this->defaultImportHeaders());
    }

    public function reuploadDraftFromUpload($file, string $batchId): void
    {
        set_time_limit(300);
        $batch = $this->importService->findBatch($batchId, "akmhs", "mahasiswa");
        [$rows, , $headers] = $this->importService->readRows($file);

        $this->importService->replaceRows(
            $batch,
            $batch->context ?? [],
            $this->validateImportRows($rows),
            [
                "original_filename" => $file->getClientOriginalName(),
                "headers" => $headers ?: $this->defaultImportHeaders(),
            ]
        );
    }

    public function updateDraft(string $batchId, array $rows): void
    {
        $batch = $this->importService->findBatch($batchId, "akmhs", "mahasiswa");
        $this->importService->replaceRows($batch, $batch->context ?? [], $this->validateImportRows($rows));
    }


    public function commit(string $batchId): array
    {
        $batch = $this->importService->findBatch($batchId, 'akmhs', 'mahasiswa');
        $this->importService->ensureDraft($batch);

        $rows = $this->validateImportRows(array_map(fn ($row) => $row['data'] ?? [], $this->importService->rows($batchId)));
        $errors = $this->importService->errors($rows);

        if (! empty($errors)) {
            $this->importService->replaceRows($batch, $batch->context ?? [], $rows);
            throw new \RuntimeException('Masih ada data mahasiswa yang perlu diperbaiki sebelum import final.');
        }

        DB::transaction(function () use ($rows) {
            $this->processImportCommit($rows);
        });

        $this->importService->markAsCompleted($batch);

        return ['success' => count($rows)];
    }

    private function processImportCommit(array $rows): void
    {
        $now = now();
        $mahasiswaRole = Role::firstOrCreate([
            'tenant_id' => sys_tenant_id(),
            'name' => 'Mahasiswa',
            'guard_name' => 'web',
        ]);

        $orgUnitMap = $this->buildOrgUnitMap();

        foreach ($rows as $row) {
            $data = $row['data'];
            $tenantId = sys_tenant_id();

            $mahasiswaPayload = [
                'nama' => $data['nama'],
                'nim' => $data['nim'] ?: null,
                'email' => $data['email'] ?: null,
                'no_hp' => $data['no_hp'] ?: null,
                'angkatan' => $data['angkatan'] ?: date('Y'),
                'jenis_masuk' => $data['jenis_masuk'] ?: 'reguler',
                'status' => $data['status'] ?: 'aktif',
                'semester_masuk' => 1,
                'prodi_id' => $orgUnitMap[strtolower($data['prodi_kode'] ?? '')] ?? null,
                'updated_at' => $now,
            ];

            $softDeleted = DB::table('akmhs_mahasiswa')
                ->where('tenant_id', $tenantId)
                ->whereNotNull('deleted_at')
                ->where(function ($q) use ($data) {
                    if ($data['nim'] !== '') {
                        $q->orWhere('nim', $data['nim']);
                    }
                })
                ->first();

            if ($softDeleted) {
                DB::table('akmhs_mahasiswa')->where('mahasiswa_id', $softDeleted->mahasiswa_id)->update([
                    'deleted_at' => null,
                    'updated_at' => $now,
                    'deleted_by' => null,
                ]);
            }

            $mahasiswaId = null;

            $existingMhs = DB::table('akmhs_mahasiswa')
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->where(function ($q) use ($data) {
                    if ($data['nim'] !== '') {
                        $q->orWhere('nim', $data['nim']);
                    }
                })
                ->first();

            if ($existingMhs) {
                DB::table('akmhs_mahasiswa')
                    ->where('mahasiswa_id', $existingMhs->mahasiswa_id)
                    ->update($mahasiswaPayload);
                $mahasiswaId = $existingMhs->mahasiswa_id;
            } else {
                $mahasiswaPayload['tenant_id'] = $tenantId;
                $mahasiswaPayload['created_at'] = $now;
                $mahasiswaId = DB::table('akmhs_mahasiswa')->insertGetId($mahasiswaPayload);
                
                DB::table('akmhs_biodata')->insert([
                    'mahasiswa_id' => $mahasiswaId,
                    'tenant_id' => $tenantId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if ($data['email'] !== '') {
                $existingUser = DB::table('users')->where('tenant_id', $tenantId)->where('email', $data['email'])->first();
                $userId = null;
                if ($existingUser) {
                    $userId = $existingUser->id;
                    DB::table('users')
                        ->where('id', $userId)
                        ->update([
                            'name' => $data['nama'],
                            'updated_at' => $now,
                        ]);
                } else {
                    $userId = DB::table('users')->insertGetId([
                        'tenant_id' => $tenantId,
                        'name' => $data['nama'],
                        'email' => $data['email'],
                        'password' => bcrypt('password'),
                        'is_active' => true,
                        'email_verified_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('model_has_roles')->insertOrIgnore([
                        'role_id' => $mahasiswaRole->id,
                        'model_type' => 'Modules\Account\Models\User',
                        'model_id' => $userId,
                        'team_id' => $tenantId,
                    ]);
                    
                    DB::table('sys_tenant_users')->insertOrIgnore([
                        'tenant_id' => sys_tenant_id(),
                        'user_id' => $userId,
                        'is_default' => 0
                    ]);
                }

                DB::table('akmhs_mahasiswa')
                    ->where('mahasiswa_id', $mahasiswaId)
                    ->update(['user_id' => $userId]);
            }
        }
    }

    private function buildOrgUnitMap(): array
    {
        $orgUnits = DB::table('hr_struktur_organisasi')
            ->where('tenant_id', sys_tenant_id())
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->select('orgunit_id', 'kode')
            ->get();

        $map = [];
        foreach ($orgUnits as $unit) {
            if (! empty($unit->kode)) {
                $map[strtolower(trim($unit->kode))] = $unit->orgunit_id;
            }
        }

        return $map;
    }

    private function validateImportRows(array $rows): array
    {
        $prepared = array_map(function ($row, $i) {
            return [
                'row' => $i + 2,
                'data' => [
                    'nim' => trim((string) ($row['nim'] ?? '')),
                    'nama' => trim((string) ($row['nama'] ?? '')),
                    'email' => strtolower(trim((string) ($row['email'] ?? ''))),
                    'no_hp' => trim((string) ($row['no_hp'] ?? '')),
                    'prodi_kode' => trim((string) ($row['prodi_kode'] ?? '')),
                    'angkatan' => trim((string) ($row['angkatan'] ?? '')),
                    'jenis_masuk' => trim((string) ($row['jenis_masuk'] ?? '')),
                    'status' => trim((string) ($row['status'] ?? '')),
                ],
                'messages' => [],
                'status' => 'valid',
            ];
        }, $rows, array_keys($rows));

        $seenEmails = [];
        $seenNims = [];

        foreach ($prepared as &$row) {
            if ($row['data']['nama'] === '') {
                $row['messages'][] = 'Nama lengkap wajib diisi.';
            }

            if ($row['data']['email'] !== '') {
                if (! filter_var($row['data']['email'], FILTER_VALIDATE_EMAIL)) {
                    $row['messages'][] = "Format email '{$row['data']['email']}' tidak valid.";
                }
                if (isset($seenEmails[$row['data']['email']])) {
                    $row['messages'][] = "Email '{$row['data']['email']}' duplikat dalam file (baris {$seenEmails[$row['data']['email']]}).";
                }
                $seenEmails[$row['data']['email']] = $row['row'];
            } else {
                $row['messages'][] = 'Email wajib diisi.';
            }

            if ($row['data']['nim'] !== '') {
                if (isset($seenNims[$row['data']['nim']])) {
                    $row['messages'][] = "NIM '{$row['data']['nim']}' duplikat dalam file (baris {$seenNims[$row['data']['nim']]}).";
                }
                $seenNims[$row['data']['nim']] = $row['row'];
            } else {
                $row['messages'][] = 'NIM wajib diisi.';
            }
        }
        unset($row);

        $payload = array_column($prepared, 'data');
        $emails = array_filter(array_column($payload, 'email'));
        $nims = array_filter(array_column($payload, 'nim'));

        $existingEmails = ! empty($emails)
            ? DB::table('users')->where('tenant_id', sys_tenant_id())->whereNull('deleted_at')->whereIn('email', $emails)->pluck('email')->all()
            : [];
        $existingNims = ! empty($nims)
            ? DB::table('akmhs_mahasiswa')->where('tenant_id', sys_tenant_id())->whereNull('deleted_at')->whereIn('nim', $nims)->pluck('nim')->all()
            : [];

        foreach ($prepared as &$row) {
            if ($row['data']['email'] !== '' && in_array($row['data']['email'], $existingEmails, true)) {
                $row['messages'][] = "Email '{$row['data']['email']}' sudah terdaftar di sistem.";
            }
            if ($row['data']['nim'] !== '' && in_array($row['data']['nim'], $existingNims, true)) {
                $row['messages'][] = "NIM '{$row['data']['nim']}' sudah terdaftar di sistem.";
            }
            $row['status'] = empty($row['messages']) ? 'valid' : 'error';
        }
        unset($row);

        return $prepared;
    }

    private function defaultImportHeaders(): array
    {
        return [
            ['key' => 'nim', 'label' => 'nim'],
            ['key' => 'nama', 'label' => 'nama'],
            ['key' => 'email', 'label' => 'email'],
            ['key' => 'no_hp', 'label' => 'no_hp'],
            ['key' => 'prodi_kode', 'label' => 'prodi_kode'],
            ['key' => 'angkatan', 'label' => 'angkatan'],
            ['key' => 'jenis_masuk', 'label' => 'jenis_masuk'],
            ['key' => 'status', 'label' => 'status'],
        ];
    }
}
