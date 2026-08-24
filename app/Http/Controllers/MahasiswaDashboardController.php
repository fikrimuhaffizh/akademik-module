<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Services\MahasiswaService;
use Modules\Akademik\Services\PeriodeAkademikService;
use Modules\HrCore\Services\StrukturOrganisasiService;

class MahasiswaDashboardController extends Controller
{
    public function __construct(
        protected MahasiswaService $mahasiswaService,
        protected PeriodeAkademikService $periodeService,
        protected StrukturOrganisasiService $strukturService,
    ) {}

    public function index()
    {
        $user = auth()->user();
        $mahasiswa = $this->mahasiswaService->getByUserId($user->id);
        abort_unless($mahasiswa, 404, 'Data mahasiswa tidak ditemukan.');

        $periode = $this->periodeService->getAktif();
        $prodiNama = $this->strukturService->getOrgUnitById($mahasiswa->prodi_id)?->name ?? '-';

        $dashboard = $this->mahasiswaService->getDashboardData($mahasiswa, $periode);

        return view('akademik::pages.dashboard.mahasiswa', array_merge(
            compact('mahasiswa', 'periode', 'prodiNama'),
            $dashboard
        ));
    }
}
