<?php

namespace Modules\Akademik\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Akademik\Models\Mahasiswa;
use Modules\Akademik\Models\KelasKuliah;
use Modules\Akademik\Models\PeriodeAkademik;

/**
 * AkademikDemoSeeder — seed remaining akd_* tables not covered by other seeders.
 * Tables: status_semester, cekal, cuti, transfer, riwayat_status,
 *         nilai_akhir, konversi_nilai, edom_kelas, edom_status, publish_batch.
 *
 * Idempotent — safe to run repeatedly.
 */
class AkademikDemoSeeder extends Seeder
{
    private int $tenantId = 1;

    public function run(): void
    {
        Model::unguard();

        $this->seedStatusSemester();
        $this->seedCekal();
        $this->seedCuti();
        $this->seedTransfer();
        $this->seedRiwayatStatus();
        $this->seedNilaiAkhir();
        $this->seedEdom();
        $this->seedPublishBatch();
    }

    private function seedStatusSemester(): void
    {
        $periode = PeriodeAkademik::where('is_aktif', true)->first();
        if (!$periode) return;

        $mahasiswas = Mahasiswa::where('tenant_id', $this->tenantId)->where('status', 'aktif')->get();
        $count = 0;

        foreach ($mahasiswas as $mhs) {
            DB::table('akd_status_semester')->updateOrInsert(
                ['mahasiswa_id' => $mhs->mahasiswa_id, 'periode_akademik_id' => $periode->periode_akademik_id, 'tenant_id' => $this->tenantId],
                ['status' => 'aktif', 'semester_ke' => $mhs->semester_masuk ?? 1, 'tenant_id' => $this->tenantId]
            );
            $count++;
        }

        $this->command?->info("  Status Semester: {$count} data.");
    }

    private function seedCekal(): void
    {
        $mahasiswas = Mahasiswa::where('tenant_id', $this->tenantId)->where('status', 'aktif')->limit(3)->get();
        $count = 0;

        foreach ($mahasiswas as $i => $mhs) {
            if ($i > 0) break; // only 1 cekal
            DB::table('akd_cekal')->updateOrInsert(
                ['mahasiswa_id' => $mhs->mahasiswa_id, 'tenant_id' => $this->tenantId, 'jenis' => 'akademik'],
                ['alasan' => 'IPK di bawah batas minimum', 'tgl_mulai' => now()->subDays(30), 'is_aktif' => true, 'tenant_id' => $this->tenantId]
            );
            $count++;
        }

        $this->command?->info("  Cekal: {$count} data.");
    }

    private function seedCuti(): void
    {
        $periode = PeriodeAkademik::where('is_aktif', true)->first();
        if (!$periode) return;

        $mahasiswas = Mahasiswa::where('tenant_id', $this->tenantId)->where('status', 'aktif')->limit(3)->get();
        $count = 0;

        foreach ($mahasiswas as $i => $mhs) {
            if ($i > 0) break; // only 1 cuti
            DB::table('akd_cuti')->updateOrInsert(
                ['mahasiswa_id' => $mhs->mahasiswa_id, 'periode_akademik_id' => $periode->periode_akademik_id, 'tenant_id' => $this->tenantId],
                ['alasan' => 'Sedang menjalani operasi', 'status' => 'pending', 'tenant_id' => $this->tenantId]
            );
            $count++;
        }

        $this->command?->info("  Cuti: {$count} data.");
    }

    private function seedTransfer(): void
    {
        $mahasiswas = Mahasiswa::where('tenant_id', $this->tenantId)->where('status', 'aktif')->limit(3)->get();
        $count = 0;

        foreach ($mahasiswas as $i => $mhs) {
            if ($i > 0) break; // only 1 transfer
            DB::table('akd_transfer')->updateOrInsert(
                ['mahasiswa_id' => $mhs->mahasiswa_id, 'tenant_id' => $this->tenantId, 'jenis' => 'antar_prodi'],
                [
                    'institusi_asal' => 'Politeknik Negeri Jakarta',
                    'prodi_asal'     => 'Teknik Informatika',
                    'sks_diakui'     => 30,
                    'semester_diakui' => 3,
                    'status'         => 'pending',
                    'tenant_id'      => $this->tenantId,
                ]
            );
            $count++;
        }

        $this->command?->info("  Transfer: {$count} data.");
    }

    private function seedRiwayatStatus(): void
    {
        $mahasiswas = Mahasiswa::where('tenant_id', $this->tenantId)->where('status', 'aktif')->limit(5)->get();
        $count = 0;

        foreach ($mahasiswas as $mhs) {
            DB::table('akd_riwayat_status')->updateOrInsert(
                ['mahasiswa_id' => $mhs->mahasiswa_id, 'tenant_id' => $this->tenantId, 'status_baru' => 'aktif'],
                [
                    'status_lama'  => 'calon',
                    'alasan'       => 'Daftar ulang semester 1',
                    'tgl_efektif'  => now()->subMonths(6),
                    'diproses_oleh' => 'System',
                    'tenant_id'    => $this->tenantId,
                ]
            );
            $count++;
        }

        $this->command?->info("  Riwayat Status: {$count} data.");
    }

    private function seedNilaiAkhir(): void
    {
        // Only if KRS details exist
        $krsDetails = DB::table('akd_krs_detail')
            ->where('tenant_id', $this->tenantId)
            ->limit(10)
            ->get();

        if ($krsDetails->isEmpty()) return;

        $periode = PeriodeAkademik::where('is_aktif', true)->first();
        if (!$periode) return;

        $count = 0;
        foreach ($krsDetails as $kd) {
            $nilai = round(rand(200, 400) / 100, 2);
            $isLulus = $nilai >= 2.0;

            DB::table('akd_nilai_akhir')->updateOrInsert(
                [
                    'mahasiswa_id'       => $kd->mahasiswa_id ?? 0,
                    'kelas_id'           => $kd->kelas_id,
                    'periode_akademik_id' => $periode->periode_akademik_id,
                    'tenant_id'          => $this->tenantId,
                ],
                [
                    'nilai_angka'  => $nilai,
                    'nilai_huruf'  => $this->scoreToLetter($nilai),
                    'bobot'        => $nilai,
                    'sks'          => 3,
                    'is_lulus'     => $isLulus,
                    'source_type'  => 'manual',
                    'tenant_id'    => $this->tenantId,
                ]
            );
            $count++;
        }

        $this->command?->info("  Nilai Akhir: {$count} data.");
    }

    private function seedEdom(): void
    {
        $kelasList = KelasKuliah::where('tenant_id', $this->tenantId)->limit(5)->get();
        $periode = PeriodeAkademik::where('is_aktif', true)->first();
        if ($kelasList->isEmpty() || !$periode) return;

        $count = 0;
        foreach ($kelasList as $kelas) {
            DB::table('akd_edom_kelas')->updateOrInsert(
                ['kelas_id' => $kelas->kelas_id, 'periode_akademik_id' => $periode->periode_akademik_id, 'tenant_id' => $this->tenantId],
                ['status' => 'draft', 'tgl_mulai' => now()->toDateString(), 'tgl_selesai' => now()->addDays(14)->toDateString(), 'tenant_id' => $this->tenantId]
            );
            $count++;
        }

        $this->command?->info("  EDOM Kelas: {$count} data.");
    }

    private function seedPublishBatch(): void
    {
        DB::table('akd_publish_batch')->updateOrInsert(
            ['reference_code' => 'DEMO-INIT', 'tenant_id' => $this->tenantId],
            [
                'source_module'   => 'Akademik',
                'total_data'      => 20,
                'success_count'   => 20,
                'conflict_count'  => 0,
                'metadata'        => json_encode(['type' => 'demo', 'note' => 'Initial demo data']),
                'tenant_id'       => $this->tenantId,
            ]
        );

        $this->command?->info("  Publish Batch: 1 data.");
    }

    private function scoreToLetter(float $score): string
    {
        return match (true) {
            $score >= 4.0 => 'A',
            $score >= 3.5 => 'B+',
            $score >= 3.0 => 'B',
            $score >= 2.5 => 'C+',
            $score >= 2.0 => 'C',
            $score >= 1.0 => 'D',
            default => 'E',
        };
    }
}
