<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\Mahasiswa;
use Modules\Akademik\Models\PembimbingMahasiswa;
use Modules\Akademik\Models\PeriodeAkademik;
use Modules\HrCore\Models\Pegawai;
use Modules\Sys\Services\ImportService;
use DB;

class PembimbingMahasiswaImportService
{
    public function __construct(protected ImportService $importService) {}

    public function processStaging(array $rows): array
    {
        $valid = [];
        $errors = [];

        foreach ($rows as $i => $row) {
            $nim = $row['nim'] ?? '';
            $nidn = $row['nidn'] ?? '';

            if (empty($nim) || empty($nidn)) {
                $errors[] = ['row' => $i + 1, 'message' => 'nim dan nidn wajib diisi.'];
                continue;
            }

            $mhs = Mahasiswa::where('nim', $nim)->first();
            if (!$mhs) {
                $errors[] = ['row' => $i + 1, 'message' => "Mahasiswa dengan NIM {$nim} tidak ditemukan."];
                continue;
            }

            $dosen = Pegawai::where('nidn', $nidn)->orWhere('nip', $nidn)->first();
            if (!$dosen) {
                $errors[] = ['row' => $i + 1, 'message' => "Dosen dengan NIDN/NIP {$nidn} tidak ditemukan."];
                continue;
            }

            $valid[] = [
                'mahasiswa_id' => $mhs->mahasiswa_id,
                'dosen_id' => $dosen->pegawai_id,
                'jenis_pembimbing' => $row['jenis_pembimbing'] ?? 'akademik',
                'periode_akademik_id' => null,
            ];
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    public function processCommit(array $rows, string $batchId): array
    {
        $periode = PeriodeAkademik::where('is_aktif', true)->first();
        $count = 0;

        foreach ($rows as $row) {
            PembimbingMahasiswa::updateOrCreate([
                'tenant_id' => sys_tenant_id(1),
                'mahasiswa_id' => $row['mahasiswa_id'],
                'dosen_id' => $row['dosen_id'],
                'periode_akademik_id' => $periode?->periode_akademik_id ?? null,
            ], [
                'jenis_pembimbing' => $row['jenis_pembimbing'],
            ]);
            $count++;
        }

        // Update batch with success count
        DB::table('sys_import_batches')
            ->where('batch_id', $batchId)
            ->update([
                'success_count' => $count,
                'conflict_count' => 0,
                'metadata' => json_encode(['periode' => $periode?->nama ?? '-']),
            ]);

        return ['success' => $count, 'errors' => []];
    }
}
