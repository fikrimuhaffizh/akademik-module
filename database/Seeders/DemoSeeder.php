<?php

namespace Modules\Akademik\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use Modules\Akademik\Models\PeriodeAkademik;
use Modules\Akademik\Models\RuangKuliah;
use Modules\Akademik\Models\PenawaranMataKuliah;
use Modules\Akademik\Models\KelasKuliah;
use Modules\Akademik\Models\PembebananDosen;
use Modules\Akademik\Models\JadwalKuliah;
use Modules\Akademik\Models\BatasSks;
use Modules\Akademik\Models\PembimbingMahasiswa;
use Modules\Akademik\Models\KalenderAkademik;

use Modules\Kurikulum\Models\Kurikulum;
use Modules\Kurikulum\Models\KurikulumMataKuliah;
use Modules\HrCore\Models\Pegawai;
use Modules\HrCore\Models\StrukturOrganisasi;

/**
 * DemoSeeder Perkuliahan — buat harusannya KRS siap diisi.
 *
 * Urutan (dependency):
 *   Periode -> Ruang -> (loop prodi: Kurikulum published -> Penawaran SEMUA MK
 *   -> Kelas -> Pembebanan 20 Dosen -> Jadwal) -> Setting Prodi (buka KRS)
 *   -> Batas SKS -> Pembimbing Mahasiswa -> Kalender.
 *
 * Idempoten (updateOrCreate) — aman dijalankan berulang.
 */
class DemoSeeder extends Seeder
{
    private int $tenant = 1;

    public function run()
    {
        Model::unguard();

        $periode = $this->seedPeriode();
        $pid = $periode->periode_akademik_id;
        $ruang = $this->seedRuang();

        // 20 dosen berbeda (dari HrCore DemoSeeder), urut by pegawai_id.
        $dosen = Pegawai::where('tenant_id', $this->tenant)
            ->where('nip', 'like', 'DU-DSN-%')
            ->orderBy('pegawai_id')
            ->get();

        // Slot jadwal non-overlap (hari+jam berbeda) — cukup untuk ratusan kelas.
        $slotJadwal = $this->buildJadwalSlots($ruang);

        $idxDosen = 0;   // round-robin antar dosen
        $idxSlot = 0;     // round-robin antar slot jadwal

        // Loop tiap kurikulum yg SUDAH published (dari Kurikulum DemoSeeder).
        $kurikulums = Kurikulum::where('tenant_id', $this->tenant)
            ->where('is_published', true)
            ->orderBy('prodi_id')
            ->get();

        foreach ($kurikulums as $kur) {
            // Setting Prodi: binding kurikulum + buka KRS (window lebar).
            // DB::table('akper_setting_prodi')->updateOrInsert(
            //     ['tenant_id' => $this->tenant, 'periode_akademik_id' => $pid, 'prodi_id' => $kur->prodi_id],
            //     [
            //         'kurikulum_id'      => $kur->kurikulum_id,
            //         'kurikulum_kode'     => $kur->kode_kurikulum,
            //         'is_aktif'          => true,
            //         'buka_krs'          => true,
            //         'tgl_krs_mulai'     => '2025-08-01',
            //         'tgl_krs_selesai'   => '2026-01-31',
            //         'buka_khs'          => true,
            //         'buka_pengisian_nilai' => true,
            //         'min_presensi_uts'  => 70,
            //         'min_presensi_uas'  => 70,
            //         'jumlah_pertemuan'  => 14,
            //         'created_at'         => now(),
            //         'updated_at'         => now(),
            //     ]
            // );

            // SEMUA MK di kurikulum ini -> Penawaran (ganjil: smt 1,3,5,7).
            $kurMks = KurikulumMataKuliah::with('mataKuliah')
                ->where('kurikulum_id', $kur->kurikulum_id)
                ->whereIn('semester', [1, 3, 5, 7])
                ->where('tenant_id', $this->tenant)
                ->orderBy('kur_mk_id')
                ->get();

            foreach ($kurMks as $kurMk) {
                $penawaran = PenawaranMataKuliah::updateOrCreate(
                    [
                        'tenant_id'                => $this->tenant,
                        'periode_akademik_id'   => $pid,
                        'kurikulum_mata_kuliah_id' => $kurMk->kur_mk_id,
                        'prodi_id'                => $kur->prodi_id,
                    ],
                    [
                        'kurikulum_kode' => $kur->kode_kurikulum,
                        'is_aktif'       => true,
                        'is_wajib'       => $kurMk->is_wajib,
                        'grup_pilihan'   => $kurMk->grup_pilihan,
                    ]
                );

                // 1 kelas per penawaran.
                $kelas = KelasKuliah::updateOrCreate(
                    ['tenant_id' => $this->tenant, 'penawaran_id' => $penawaran->penawaran_id, 'nama_kelas' => 'A'],
                    ['kapasitas' => 40, 'is_aktif' => true]
                );

                // Pembebanan: 1 dosen per kelas, round-robin agar tiap dosen 1-2 jadwal.
                $pg = $dosen->isNotEmpty() ? $dosen[$idxDosen % $dosen->count()] : null;
                $idxDosen++;
                if ($pg) {
                    PembebananDosen::updateOrCreate(
                        ['tenant_id' => $this->tenant, 'kelas_id' => $kelas->kelas_id, 'pegawai_id' => $pg->pegawai_id, 'peran' => 'pengampu'],
                        []
                    );
                }

                // Jadwal: slot non-overlap (rotasi hari/jam/ruang).
                $slot = $slotJadwal[$idxSlot % count($slotJadwal)];
                $idxSlot++;
                JadwalKuliah::updateOrCreate(
                    ['tenant_id' => $this->tenant, 'kelas_id' => $kelas->kelas_id, 'hari' => $slot['hari'], 'jam_mulai' => $slot['jam_mulai']],
                    [
                        'ruang_id'      => $slot['ruang']->ruang_id,
                        'jam_selesai'   => $slot['jam_selesai'],
                        'jenis_pertemuan' => 'teori',
                        'link_online'   => null,
                    ]
                );
            }
        }

        $this->seedBatasSks($pid);
        $this->seedPembimbingMahasiswa($pid, $dosen);
        $this->seedKalender($pid);

        $this->command?->info("Demo Perkuliahan: {$kurikulums->count()} kurikulum diproses, {$dosen->count()} dosen dialokasikan.");
    }

    // ── Periode Akademik (ganjil aktif) ──
    private function seedPeriode(): PeriodeAkademik
    {
        return PeriodeAkademik::updateOrCreate(
            ['nama' => '2025/2026 Ganjil', 'tenant_id' => $this->tenant],
            [
                'tahun_mulai' => '2025',
                'tahun_selesai' => '2026',
                'semester' => 'ganjil',
                'tgl_mulai' => '2025-08-01',
                'tgl_selesai' => '2026-01-31',
                'is_aktif' => true,
            ]
        );
    }

    // ── Ruang Kuliah (master) ──
    private function seedRuang(): array
    {
        $a = RuangKuliah::updateOrCreate(
            ['tenant_id' => $this->tenant, 'kode' => 'R-A1'],
            ['nama' => 'Ruang Kelas A1', 'gedung' => 'Gedung A', 'lantai' => 1, 'kapasitas' => 40, 'jenis' => 'kelas', 'is_aktif' => true]
        );
        $b = RuangKuliah::updateOrCreate(
            ['tenant_id' => $this->tenant, 'kode' => 'R-B1'],
            ['nama' => 'Ruang Kelas B1', 'gedung' => 'Gedung B', 'lantai' => 1, 'kapasitas' => 40, 'jenis' => 'kelas', 'is_aktif' => true]
        );
        $lab = RuangKuliah::updateOrCreate(
            ['tenant_id' => $this->tenant, 'kode' => 'LAB-KOM'],
            ['nama' => 'Lab Komputer', 'gedung' => 'Gedung C', 'lantai' => 2, 'kapasitas' => 30, 'jenis' => 'lab', 'is_aktif' => true]
        );

        return [$a, $b, $lab];
    }

    /**
     * Slot jadwal non-overlap: kombinasi (hari, jam, ruang) berbeda.
     * Cukup banyak agar ratusan kelas t DK bentrok.
     */
    private function buildJadwalSlots(array $ruang): array
    {
        $hari = ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
        $jam = [
            ['08:00', '10:30'],
            ['10:30', '13:00'],
            ['13:00', '15:30'],
            ['15:30', '18:00'],
        ];
        $slots = [];
        foreach ($hari as $h) {
            foreach ($jam as [$jm, $js]) {
                foreach ($ruang as $r) {
                    $slots[] = ['hari' => $h, 'jam_mulai' => $jm, 'jam_selesai' => $js, 'ruang' => $r];
                }
            }
        }

        return $slots; // 5 x 4 x 3 = 60 slot unik
    }

    private function seedBatasSks(int $pid): void
    {
        $batas = [
            ['ipk_min' => 0.00, 'ipk_max' => 2.00, 'max_sks' => 18],
            ['ipk_min' => 2.00, 'ipk_max' => 3.00, 'max_sks' => 21],
            ['ipk_min' => 3.00, 'ipk_max' => 4.00, 'max_sks' => 24],
        ];
        foreach ($batas as $b) {
            BatasSks::updateOrCreate(
                ['periode_akademik_id' => $pid, 'ipk_min' => $b['ipk_min'], 'ipk_max' => $b['ipk_max']],
                ['max_sks' => $b['max_sks']]
            );
        }
    }

    private function seedPembimbingMahasiswa(int $pid, $dosen): void
        {
            if (!Schema::hasTable('akd_mahasiswa')) {
                $this->command?->warn('Tabel akd_mahasiswa belum ada, lewati seedPembimbingMahasiswa.');
                return;
            }

            $mahasiswas = DB::table('akd_mahasiswa')
                ->where('tenant_id', $this->tenant)
                ->orderBy('mahasiswa_id')
                ->get();

            if ($mahasiswas->isEmpty() || $dosen->isEmpty()) {
                return;
            }

            foreach ($mahasiswas as $i => $mhs) {
                $pg = $dosen[$i % $dosen->count()];
                PembimbingMahasiswa::updateOrCreate(
                    ['tenant_id' => $this->tenant, 'periode_akademik_id' => $pid, 'mahasiswa_id' => $mhs->mahasiswa_id],
                    ['pegawai_id' => $pg->pegawai_id]
                );
            }
        }

    private function seedKalender(int $pid): void
    {
        KalenderAkademik::updateOrCreate(
            ['tenant_id' => $this->tenant, 'periode_akademik_id' => $pid, 'nama_kegiatan' => 'Kuliah Perdana'],
            ['tgl_mulai' => '2025-08-01', 'tgl_selesai' => '2025-08-01', 'jenis' => 'akademik', 'keterangan' => 'Awal perkuliahan ganjil']
        );
        KalenderAkademik::updateOrCreate(
            ['tenant_id' => $this->tenant, 'periode_akademik_id' => $pid, 'nama_kegiatan' => 'Ujian Tengah Semester'],
            ['tgl_mulai' => '2025-10-13', 'tgl_selesai' => '2025-10-25', 'jenis' => 'ujian', 'keterangan' => 'UTS']
        );
        KalenderAkademik::updateOrCreate(
            ['tenant_id' => $this->tenant, 'periode_akademik_id' => $pid, 'nama_kegiatan' => 'Evaluasi Dosen Mengajar (EDOM)'],
            ['tgl_mulai' => now()->startOfMonth()->toDateString(), 'tgl_selesai' => now()->addMonth()->endOfMonth()->toDateString(), 'jenis' => 'edom', 'keterangan' => 'Pengisian EDOM oleh mahasiswa']
        );
    }
}
