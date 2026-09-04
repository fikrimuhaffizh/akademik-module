<?php

namespace Modules\Akademik\Services;

use Illuminate\Support\Facades\DB;
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
        // Consolidate 6 separate count queries into 1 grouped query
        $statusCounts = Mahasiswa::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingCounts = DB::table('akd_cekal')->where('is_aktif', true)->count()
            + DB::table('akd_cuti')->where('status', 'pending')->count()
            + DB::table('akd_transfer')->where('status', 'pending')->count()
            + DB::table('akd_krs')->where('status', 'diajukan')->count();

        return [
            'total_mahasiswa'  => $statusCounts->sum(),
            'aktif'            => $statusCounts->get('aktif', 0),
            'cuti'             => $statusCounts->get('cuti', 0),
            'do'               => $statusCounts->get('do', 0),
            'lulus'            => $statusCounts->get('lulus', 0),
            'calon'            => $statusCounts->get('calon', 0),
            'tercekal'         => DB::table('akd_cekal')->where('is_aktif', true)->count(),
            'cuti_pending'     => DB::table('akd_cuti')->where('status', 'pending')->count(),
            'transfer_pending' => DB::table('akd_transfer')->where('status', 'pending')->count(),
            'krs_pending'      => DB::table('akd_krs')->where('status', 'diajukan')->count(),
            'kelas_active'     => KelasKuliah::where('is_aktif', true)->count(),
            'periode_aktif'    => PeriodeAkademik::where('is_aktif', true)->first(),
        ];
    }
}
