<?php

namespace Modules\Akademik\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Referensi\Models\SysRef;

/**
 * DataSeeder - data default tenant baru untuk Akademik.
 *
 * Isi: grup `pembimbing_mahasiswa` di tabel `sys_refs` (PA/PL/P1/P2).
 * Idempotent - updateOrCreate, aman dijalankan berulang.
 */
class DataSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = sys_tenant_id(null) ?? 1;

        // REFERENSI PEMBIMBING
        // grup: pembimbing_mahasiswa - dipakai dropdown pembimbing di form TA/skripsi.
        $data = [
            ['tenant_id' => $tenantId, 'grup' => 'pembimbing_mahasiswa', 'kode' => 'PA',   'label' => 'Pembimbing Akademik', 'urutan' => 1],
            ['tenant_id' => $tenantId, 'grup' => 'pembimbing_mahasiswa', 'kode' => 'PL',   'label' => 'Pembimbing Lapangan', 'urutan' => 2],
            ['tenant_id' => $tenantId, 'grup' => 'pembimbing_mahasiswa', 'kode' => 'P1',   'label' => 'Pembimbing 1',        'urutan' => 3],
            ['tenant_id' => $tenantId, 'grup' => 'pembimbing_mahasiswa', 'kode' => 'P2',   'label' => 'Pembimbing 2',        'urutan' => 4],
        ];

        foreach ($data as $item) {
            SysRef::updateOrCreate(
                [
                    'tenant_id' => $item['tenant_id'],
                    'grup' => $item['grup'],
                    'kode' => $item['kode'],
                ],
                [
                    'label' => $item['label'],
                    'urutan' => $item['urutan'],
                    'is_aktif' => true,
                ]
            );
        }

        $this->command->info('✅ Seed pembimbing_mahasiswa refs: '.count($data).' data referensi berhasil disync.');
    }
}
