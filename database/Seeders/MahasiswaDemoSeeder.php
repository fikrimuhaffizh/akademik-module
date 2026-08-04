<?php

namespace Modules\Akademik\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Akademik\Models\Mahasiswa;
use Modules\Akademik\Models\Biodata;
use Modules\Kurikulum\Models\Kurikulum;

/**
 * MahasiswaDemoSeeder — buat 20 sample mahasiswa (4/prodi).
 * Idempoten (updateOrCreate by nim).
 */
class MahasiswaDemoSeeder extends Seeder
{
    private int $tenantId = 1;
    private array $prodiIds = [31, 32, 33, 34, 35];

    public function run(): void
    {
        Model::unguard();

        $namaPerProdi = [
            31 => ['Andi Pratama', 'Dewi Lestari', 'Budi Santoso', 'Rina Melati'],
            32 => ['Rizky Aditya', 'Putri Anjani', 'Dimas Firmansyah', 'Sari Dewi'],
            33 => ['Fajar Nugroho', 'Maya Indah', 'Hendra Wijaya', 'Lina Sari'],
            34 => ['Yoga Saputra', 'Nina Marlina', 'Ari Gunawan', 'Wati Susilawati'],
            35 => ['Rina Nose', 'Siti Nurhaliza', 'Teguh Karya', 'Wulan Damar'],
        ];

        $kurByProdi = [];
        foreach ($this->prodiIds as $pid) {
            $setting = null;
            if (Schema::hasTable('akper_setting_prodi')) {
                $setting = DB::table('akper_setting_prodi')
                    ->where('prodi_id', $pid)
                    ->where('tenant_id', $this->tenantId)
                    ->where('is_aktif', true)
                    ->first();
            }
            $kur = $setting && $setting->kurikulum_id
                ? Kurikulum::where('kurikulum_id', $setting->kurikulum_id)->first()
                : Kurikulum::where('prodi_id', $pid)
                    ->where('tenant_id', $this->tenantId)
                    ->where('is_published', true)
                    ->first();
            $kurByProdi[$pid] = $kur;
        }

        $counter = 0;
        foreach ($this->prodiIds as $pid) {
            $kur = $kurByProdi[$pid] ?? null;
            $angkatan = 2024;
            foreach ($namaPerProdi[$pid] as $nama) {
                $counter++;
                $nim = $angkatan . str_pad($counter, 4, '0', STR_PAD_LEFT);

                $mhs = Mahasiswa::updateOrCreate(
                    ['nim' => $nim, 'tenant_id' => $this->tenantId],
                    [
                        'nama'          => $nama,
                        'prodi_id'      => $pid,
                        'angkatan'      => $angkatan,
                        'status'        => 'aktif',
                        'jenis_masuk'   => 'reguler',
                        'kurikulum_kode' => $kur?->kode_kurikulum,
                    ]
                );

                Biodata::updateOrCreate(
                    ['mahasiswa_id' => $mhs->mahasiswa_id, 'tenant_id' => $this->tenantId],
                    [
                        'nik'           => '327312345678' . str_pad($counter, 4, '0', STR_PAD_LEFT),
                        'tempat_lahir'  => 'Jakarta',
                        'tgl_lahir'     => '2000-01-' . str_pad(($counter % 28) + 1, 2, '0', STR_PAD_LEFT),
                        'jenis_kelamin' => $counter % 2 === 0 ? 'L' : 'P',
                        'agama'         => 'Islam',
                        'alamat'        => 'Jl. Sudirman No. ' . $counter,
                    ]
                );
            }
        }

        $this->command?->info("Demo Mahasiswa: {$counter} mahasiswa tersebar merata per prodi (4/prodi).");
    }
}
