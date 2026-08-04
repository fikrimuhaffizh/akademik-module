<?php

namespace Modules\Akademik\Services;

use Modules\Akademik\Models\Mahasiswa;
use Modules\Akademik\Models\Krs;
use Modules\Akademik\Models\Cekal;
use Modules\Akademik\Models\Cuti;
use Modules\Akademik\Models\Transfer;
use Modules\Akademik\Models\PeriodeAkademik;
use Modules\Akademik\Models\KelasKuliah;

class DashboardService
{
    public function getAdminStats(): array
    {
        return [
            'total_mahasiswa'  => Mahasiswa::count(),
            'aktif'            => Mahasiswa::where('status', 'aktif')->count(),
            'cuti'             => Mahasiswa::where('status', 'cuti')->count(),
            'do'               => Mahasiswa::where('status', 'do')->count(),
            'lulus'            => Mahasiswa::where('status', 'lulus')->count(),
            'calon'            => Mahasiswa::where('status', 'calon')->count(),
            'tercekal'         => Cekal::where('is_aktif', true)->count(),
            'cuti_pending'     => Cuti::where('status', 'pending')->count(),
            'transfer_pending' => Transfer::where('status', 'pending')->count(),
            'krs_pending'      => Krs::where('status', 'diajukan')->count(),
            'kelas_active'     => KelasKuliah::where('is_aktif', true)->count(),
            'periode_aktif'    => PeriodeAkademik::where('is_aktif', true)->first(),
        ];
    }
}
