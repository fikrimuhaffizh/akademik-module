<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Models\Mahasiswa;
use Modules\Akademik\Models\Krs;
use Modules\Akademik\Models\Nilai;
use Modules\Akademik\Models\Cekal;
use Modules\Akademik\Models\Cuti;
use Modules\Akademik\Services\NilaiService;
use Modules\Akademik\Services\PeriodeAkademikService;
use Modules\HrCore\Services\StrukturOrganisasiService;
use Modules\Kurikulum\Models\KurikulumMataKuliah;
use Modules\Kurikulum\Models\SettingProdi;

class MahasiswaDashboardController extends Controller
{
    public function __construct(
        protected NilaiService $nilaiService,
        protected PeriodeAkademikService $periodeService,
        protected StrukturOrganisasiService $strukturService,
    ) {}

    public function index()
    {
        $user = auth()->user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->firstOrFail();
        $periode = $this->periodeService->getAktif();

        // Prodi name
        $prodiNama = $this->strukturService->getOrgUnitById($mahasiswa->prodi_id)?->name ?? '-';

        // KRS aktif
        $krsAktif = $periode
            ? Krs::where('mahasiswa_id', $mahasiswa->mahasiswa_id)
                ->where('periode_akademik_id', $periode->periode_akademik_id)
                ->first()
            : null;

        // SKS diambil semester ini
        $sksDiambil = $krsAktif
            ? $krsAktif->details()->where('status', 'aktif')->count() // simplified
            : 0;

        // IPK & IPS
        $ipk = $this->nilaiService->hitungIpk($mahasiswa->mahasiswa_id);
        $ips = $periode
            ? $this->nilaiService->hitungIps($mahasiswa->mahasiswa_id, $periode->periode_akademik_id)
            : 0;

        // Jumlah SKS lulus
        $sksLulus = Nilai::where('mahasiswa_id', $mahasiswa->mahasiswa_id)
            ->where('is_lulus', true)
            ->sum('sks');

        // Cekal aktif
        $cekalAktif = Cekal::where('mahasiswa_id', $mahasiswa->mahasiswa_id)
            ->where('is_aktif', true)
            ->first();

        // Riwayat nilai terakhir (5)
        $nilaiTerakhir = Nilai::with('mataKuliah')
            ->where('mahasiswa_id', $mahasiswa->mahasiswa_id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Cuti
        $cutiAktif = Cuti::where('mahasiswa_id', $mahasiswa->mahasiswa_id)
            ->where('status', 'pending')
            ->exists();

        // Progress kelulusan — total SKS dari kurikulum prodi mahasiswa
        $totalSksKurikulum = 0;
        $settingProdi = SettingProdi::where('prodi_id', $mahasiswa->prodi_id)
            ->where('is_aktif', true)
            ->first();
        if ($settingProdi && $settingProdi->kurikulum_id) {
            $totalSksKurikulum = KurikulumMataKuliah::where('kurikulum_id', $settingProdi->kurikulum_id)
                ->sum('sks');
        }
        $persentaseKelulusan = $totalSksKurikulum > 0
            ? round(($sksLulus / $totalSksKurikulum) * 100, 1)
            : 0;

        return view('akademik::pages.dashboard.mahasiswa', compact(
            'mahasiswa', 'periode', 'prodiNama', 'krsAktif', 'sksDiambil',
            'ipk', 'ips', 'sksLulus', 'totalSksKurikulum', 'persentaseKelulusan',
            'cekalAktif', 'nilaiTerakhir', 'cutiAktif'
        ));
    }
}
