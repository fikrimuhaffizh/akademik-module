<?php

namespace Modules\Akademik\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Modules\Akademik\Models\EdomKelas;
use Modules\Akademik\Models\EdomStatus;
use Modules\Akademik\Models\KalenderAkademik;
use Modules\Akademik\Models\KelasKuliah;
use Modules\Akademik\Models\Krs;
use Modules\Akademik\Models\KrsDetail;
use Modules\Akademik\Models\Mahasiswa;
use Modules\Akademik\Models\PeriodeAkademik;
use Modules\Survei\Models\Survei\Jawaban;
use Modules\Survei\Models\Survei\Pengisian;
use Modules\Survei\Models\Survei\Survei;

class EdomService
{
    /**
     * Generate edom_status berdasarkan Kelas Kuliah di periode akademik tertentu.
     *
     * Untuk setiap Kelas Kuliah di periode tsb, kita ambil mahasiswa yang
     * terdaftar (dari KRS yang disetujui → krs_detail dengan kelas_id sama),
     * lalu buat satu EdomStatus per (mahasiswa, kelas). Ini menghasilkan:
     *   Andi  → Kelas A (Dosen A1)
     *   Budi  → Kelas B (Dosen B1 & B2)
     *   Citra → Kelas B (Dosen B1 & B2)
     */
    public function generateForPeriode(int $periodeAkademikId): int
    {
        return DB::transaction(function () use ($periodeAkademikId) {
            $kelasList = KelasKuliah::with('pembebananDosens')
                ->whereHas('penawaran', fn ($q) => $q->where('periode_akademik_id', $periodeAkademikId))
                ->get();

            $created = 0;

            foreach ($kelasList as $kelas) {
                // mahasiswa_id ada di tabel krs (bukan krs_detail)
                $krsIds = KrsDetail::query()
                    ->where('kelas_id', $kelas->kelas_id)
                    ->where('status', 'aktif')
                    ->whereHas('krs', fn ($q) => $q->where('periode_akademik_id', $periodeAkademikId)
                        ->where('status', 'disetujui'))
                    ->pluck('krs_id')
                    ->unique()
                    ->values();

                $mahasiswaIds = Krs::whereIn('krs_id', $krsIds)
                    ->pluck('mahasiswa_id')
                    ->unique()
                    ->values();

                foreach ($mahasiswaIds as $mahasiswaId) {
                    $exists = EdomStatus::where('periode_akademik_id', $periodeAkademikId)
                        ->where('mahasiswa_id', $mahasiswaId)
                        ->where('kelas_id', $kelas->kelas_id)
                        ->exists();

                    if (!$exists) {
                        EdomStatus::create([
                            'tenant_id' => sys_tenant_id(),
                            'periode_akademik_id' => $periodeAkademikId,
                            'mahasiswa_id' => $mahasiswaId,
                            'kelas_id' => $kelas->kelas_id,
                            'status' => 'belum_mulai',
                        ]);
                        $created++;
                    }
                }
            }

            return $created;
        });
    }

    /**
     * Get rekap progress per kelas untuk admin.
     */
    public function getRekapByKelas(int $periodeAkademikId): SupportCollection
    {
        $rataRata = $this->getRataRataPerKelas($periodeAkademikId);

        return EdomStatus::with(['kelas.penawaran.kurikulumMataKuliah.mataKuliah', 'kelas.pembebananDosens.pegawai'])
            ->where('periode_akademik_id', $periodeAkademikId)
            ->get()
            ->groupBy('kelas_id')
            ->map(function ($items, $kelasId) use ($rataRata) {
                $kelas = $items->first()->kelas;
                $total = $items->count();
                $selesai = $items->where('status', 'selesai')->count();
                $sedangDiisi = $items->where('status', 'sedang_diisi')->count();
                $belumMulai = $items->where('status', 'belum_mulai')->count();

                $dosens = $kelas->pembebananDosens->map(fn ($p) => $p->pegawai?->nama ?? '-')->filter()->unique()->values();

                return [
                    'kelas_id' => $kelasId,
                    'nama_kelas' => $kelas->nama_kelas ?? '-',
                    'mata_kuliah' => $kelas->penawaran?->kurikulumMataKuliah?->mataKuliah?->nama ?? '-',
                    'kode_mata_kuliah' => $kelas->penawaran?->kurikulumMataKuliah?->mataKuliah?->kode ?? '-',
                    'dosen' => $dosens->implode(', ') ?: '-',
                    'total' => $total,
                    'selesai' => $selesai,
                    'sedang_diisi' => $sedangDiisi,
                    'belum_mulai' => $belumMulai,
                    'persentase' => $total > 0 ? round(($selesai / $total) * 100) : 0,
                    'rata_rata' => $rataRata[$kelasId] ?? null,
                ];
            });
    }

    /**
     * Get list matakuliah untuk mahasiswa yang sedang login.
     */
    public function getListForMahasiswa(int $mahasiswaId, int $periodeAkademikId): EloquentCollection
    {
        return EdomStatus::with(['kelas.penawaran.kurikulumMataKuliah.mataKuliah', 'kelas.pembebananDosens'])
            ->where('periode_akademik_id', $periodeAkademikId)
            ->where('mahasiswa_id', $mahasiswaId)
            ->get();
    }

    /**
     * Get active periode EDOM.
     */
    public function getActivePeriode(): ?PeriodeAkademik
    {
        return PeriodeAkademik::where('is_aktif', true)->first();
    }

    /**
     * Mulai isi EDOM — update status menjadi sedang_diisi.
     *
     * WAJIB cek kepemilikan — $userId harus merupakan
     * mahasiswa pemilik EdomStatus. Sebelumnya parameter ini diabaikan sehingga
     * user bisa mengubah status EDOM milik mahasiswa lain (IDOR).
     */
    public function mulaiIsi(int $edomStatusId, int $userId): EdomStatus
    {
        $item = EdomStatus::findOrFail($edomStatusId);

        $mahasiswa = Mahasiswa::where('user_id', $userId)->first();
        abort_if($mahasiswa === null || (int) $item->mahasiswa_id !== (int) $mahasiswa->mahasiswa_id, 403, 'Anda tidak berhak mengakses EDOM ini.');

        if ($item->status === 'selesai') {
            abort(422, 'EDOM untuk matakuliah ini sudah selesai diisi.');
        }

        $item->update([
            'status' => 'sedang_diisi',
            'waktu_mulai' => $item->waktu_mulai ?? now(),
        ]);

        return $item->fresh();
    }

    /**
     * Selesai isi EDOM — update status menjadi selesai.
     */
    public function selesaiIsi(int $edomStatusId, int $surveiPengisianId): EdomStatus
    {
        $item = EdomStatus::findOrFail($edomStatusId);

        $item->update([
            'status' => 'selesai',
            'survei_pengisian_id' => $surveiPengisianId,
            'waktu_selesai' => now(),
        ]);

        return $item->fresh();
    }

    /**
     * Get stats summary untuk admin dashboard.
     */
    public function getStats(int $periodeAkademikId): array
    {
        $total = EdomStatus::where('periode_akademik_id', $periodeAkademikId)->count();
        $selesai = EdomStatus::where('periode_akademik_id', $periodeAkademikId)->where('status', 'selesai')->count();
        $sedangDiisi = EdomStatus::where('periode_akademik_id', $periodeAkademikId)->where('status', 'sedang_diisi')->count();
        $belumMulai = EdomStatus::where('periode_akademik_id', $periodeAkademikId)->where('status', 'belum_mulai')->count();

        return [
            'total' => $total,
            'selesai' => $selesai,
            'sedang_diisi' => $sedangDiisi,
            'belum_mulai' => $belumMulai,
            'persentase' => $total > 0 ? round(($selesai / $total) * 100) : 0,
        ];
    }

    /**
     * Cari event Kalender Akademik jenis='edom' yang sedang aktif (range tanggal).
     */
    public function getActiveEdomEvent(): ?KalenderAkademik
    {
        return KalenderAkademik::where('jenis', 'edom')
            ->whereDate('tgl_mulai', '<=', now())
            ->whereDate('tgl_selesai', '>=', now())
            ->orderByDesc('tgl_mulai')
            ->first();
    }

    /**
     * Survei EDOM bersama (shared) untuk semua kelas — diidentifikasi via slug tetap.
     */
    public function getEdomSurvei(): ?Survei
    {
        return Survei::where('slug', 'edom-mahasiswa')->first();
    }

    /**
     * Cek apakah sebuah EdomStatus sudah selesai diisi di modul Survei.
     * Pembeda per matakuliah = entitas_target (EdomStatus) pada survei_pengisian.
     */
    public function isSelesaiDiSurvei(EdomStatus $edomStatus): bool
    {
        return Pengisian::where('entitas_target_type', EdomStatus::class)
            ->where('entitas_target_id', $edomStatus->edom_status_id)
            ->where('status', 'Selesai')
            ->exists();
    }

    /**
     * Sinkron status EdomStatus dari Survei (lazy) — memudahkan monitoring
     * tanpa callback antar-modul.
     */
    public function syncDariSurvei(EdomStatus $edomStatus): EdomStatus
    {
        $pengisian = Pengisian::where('entitas_target_type', EdomStatus::class)
            ->where('entitas_target_id', $edomStatus->edom_status_id)
            ->where('status', 'Selesai')
            ->first();

        if ($pengisian && $edomStatus->status !== 'selesai') {
            $edomStatus->update([
                'status' => 'selesai',
                'survei_pengisian_id' => $pengisian->pengisian_id,
                'waktu_selesai' => $pengisian->waktu_selesai,
            ]);
            $edomStatus->refresh();
        }

        return $edomStatus;
    }

    /**
     * Nilai rata-rata EDOM per kelas untuk periode tertentu,
     * diambil dari jawaban Survei (entitas_target = EdomStatus → kelas).
     * Mengembalikan map: kelas_id => rata-rata skala (1-5) atau null.
     */
    public function getRataRataPerKelas(int $periodeAkademikId): array
    {
        $statuses = EdomStatus::with('kelas')
            ->where('periode_akademik_id', $periodeAkademikId)
            ->whereNotNull('survei_pengisian_id')
            ->get();

        $pengisianPerKelas = [];
        foreach ($statuses as $st) {
            if ($st->kelas_id) {
                $pengisianPerKelas[$st->kelas_id][] = $st->survei_pengisian_id;
            }
        }

        $result = [];
        foreach ($pengisianPerKelas as $kelasId => $pengisianIds) {
            $avg = Jawaban::whereIn('pengisian_id', $pengisianIds)
                ->whereNotNull('nilai_angka')
                ->avg('nilai_angka');
            $result[$kelasId] = $avg !== null ? round((float) $avg, 2) : null;
        }

        return $result;
    }

    /**
     * Status EDOM per kelas untuk halaman rekap.
     */
    public function getStatusesForRekap(int $periodeAkademikId, int $kelasId): EloquentCollection
    {
        return EdomStatus::with(['kelas.penawaran.kurikulumMataKuliah.mataKuliah'])
            ->where('periode_akademik_id', $periodeAkademikId)
            ->where('kelas_id', $kelasId)
            ->get();
    }

    /**
     * Konfigurasi EDOM kelas (akper_edom_kelas) untuk redirect ke Survei.
     */
    public function findKelasConfig(int $kelasId, int $periodeAkademikId): ?EdomKelas
    {
        return EdomKelas::where('kelas_id', $kelasId)
            ->where('periode_akademik_id', $periodeAkademikId)
            ->first();
    }

    /**
     * Status EDOM per mahasiswa di periode tertentu, keyBy kelas_id.
     */
    public function getStatusesByMahasiswa(int $periodeAkademikId, int $mahasiswaId): EloquentCollection
    {
        return EdomStatus::where('periode_akademik_id', $periodeAkademikId)
            ->where('mahasiswa_id', $mahasiswaId)
            ->get()
            ->keyBy('kelas_id');
    }

    /**
     * Pastikan baris EdomStatus ada (firstOrCreate) untuk 1 kelas mahasiswa.
     */
    public function ensureStatus(int $periodeAkademikId, int $mahasiswaId, int $kelasId): EdomStatus
    {
        return EdomStatus::firstOrCreate(
            [
                'periode_akademik_id' => $periodeAkademikId,
                'mahasiswa_id' => $mahasiswaId,
                'kelas_id' => $kelasId,
            ],
            [
                'tenant_id' => sys_tenant_id(),
                'status' => 'belum_mulai',
            ]
        );
    }
}
