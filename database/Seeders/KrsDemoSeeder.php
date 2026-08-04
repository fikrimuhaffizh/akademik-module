<?php

namespace Modules\Akademik\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Akademik\Models\KelasKuliah;
use Modules\Akademik\Models\Krs;
use Modules\Akademik\Models\KrsDetail;
use Modules\Akademik\Models\PeriodeAkademik;
use Modules\Akademik\Models\KalenderAkademik;
use Modules\Akademik\Models\Mahasiswa;

class KrsDemoSeeder extends Seeder
{
    /**
     * Buat 1 KRS contoh: 1 mahasiswa mengambil 3 kelas (PW/BD/ALG kelas A)
     * lalu disetujui, sehingga siap untuk enrollment LMS / pengisian nilai.
     *
     * Idempoten — lewati bila KRS untuk mahasiswa+periode tsb sudah ada.
     */
    public function run()
    {
        $tenant = sys_tenant_id(1);

        $periode = PeriodeAkademik::where('is_aktif', true)->first();
        if (! $periode) {
            $this->command?->warn('Tidak ada periode aktif, lewati KrsDemoSeeder.');
            return;
        }
        $pid = $periode->periode_akademik_id;

        $mahasiswa = Mahasiswa::query()->orderByDesc('created_at')->first();
        if (! $mahasiswa) {
            $this->command?->warn('Tidak ada mahasiswa, lewati KrsDemoSeeder.');
            return;
        }
        $mahasiswaId = $mahasiswa->mahasiswa_id;

        // Pastikan event Kalender Akademik jenis='krs' untuk periode aktif
        // dengan window lebar agar validasi KrsService (isKrsOpen) lolos.
        KalenderAkademik::updateOrCreate(
            ['tenant_id' => $tenant, 'periode_akademik_id' => $pid, 'jenis' => 'krs'],
            [
                'nama_kegiatan' => 'Pengisian KRS (Demo)',
                'tgl_mulai' => '2025-07-01',
                'tgl_selesai' => '2026-12-31',
                'keterangan' => 'Window KRS demo lebar',
            ]
        );

        // Ambil 3 kelas A dari periode aktif (PW101-A, BD101-A, ALG101-A).
        $kelas = KelasKuliah::with('penawaran')
            ->whereHas('penawaran', fn ($q) => $q->where('periode_akademik_id', $pid))
            ->where('nama_kelas', 'A')
            ->limit(3)
            ->get();

        if ($kelas->count() < 3) {
            $this->command?->warn('Kelas A periode aktif kurang dari 3, lewati KrsDemoSeeder.');
            return;
        }
        $kelasIds = $kelas->pluck('kelas_id')->all();

        // Idempoten: lewati bila sudah ada KRS untuk mahasiswa+periode ini.
        $existing = Krs::where('mahasiswa_id', $mahasiswaId)
            ->where('periode_akademik_id', $pid)
            ->first();

        if ($existing) {
            $this->command?->info("KRS untuk mahasiswa #{$mahasiswaId} periode #{$pid} sudah ada (status: {$existing->status}).");
            return;
        }

        // Buat KRS langsung (bypass validasi bisnis KrsService: kuota/IPK/prasyarat)
        // karena ini data demo yang perlu selalu terbentuk.
        $totalSks = (int) $kelas->sum(
            fn ($item) => (int) ($item->penawaran?->kurikulumMataKuliah?->mataKuliah?->sks ?? 0)
        );

        $krs = Krs::create([
            'tenant_id' => $tenant,
            'mahasiswa_id' => $mahasiswaId,
            'periode_akademik_id' => $pid,
            'total_sks' => $totalSks,
            'status' => 'disetujui',
            'disetujui_oleh' => 1,
            'tgl_disetujui' => now(),
        ]);

        foreach ($kelasIds as $kelasId) {
            KrsDetail::updateOrCreate(
                ['krs_id' => $krs->krs_id, 'kelas_id' => $kelasId],
                ['tenant_id' => $tenant, 'status' => 'aktif']
            );
        }

        $this->command?->info(
            "KRS #{$krs->krs_id} dibuat & disetujui: mahasiswa #{$mahasiswaId}, {$krs->total_sks} SKS, "
            . count($kelasIds) . " kelas."
        );
    }
}
