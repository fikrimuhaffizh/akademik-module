<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Models\EdomStatus;
use Modules\Akademik\Services\EdomService;
use Modules\Akademik\Services\KelasKuliahService;
use Modules\Akademik\Services\KrsService;
use Modules\Akademik\Services\MahasiswaService;
use Modules\Survei\Models\Survei\Survei;

class EdomMahasiswaController extends Controller
{
    public function __construct(
        protected EdomService $edomService,
        protected KelasKuliahService $kelasKuliahService,
        protected KrsService $krsService,
        protected MahasiswaService $mahasiswaService,
    ) {}

    public function index()
    {
        $user = auth()->user();
        $mahasiswa = $this->mahasiswaService->getByUserId($user->id);

        if (! $mahasiswa) {
            return view('akademik::pages.edom.index', [
                'mahasiswa' => null,
                'event' => null,
                'survei' => null,
                'kelasList' => collect(),
                'pesan' => 'Akun Anda tidak terhubung dengan data mahasiswa.',
            ]);
        }

        $event = $this->edomService->getActiveEdomEvent();

        if (! $event) {
            return view('akademik::pages.edom.index', [
                'mahasiswa' => $mahasiswa,
                'event' => null,
                'survei' => null,
                'kelasList' => collect(),
                'pesan' => 'Jadwal pengisian EDOM belum dibuka untuk periode saat ini.',
            ]);
        }

        $periodeId = $event->periode_akademik_id;
        $survei = $this->edomService->getEdomSurvei();

        // Kelas kuliah yang diambil mahasiswa di periode EDOM (via KRS detail → krs).
        $kelasIds = $this->krsService->getKelasIdsByMahasiswaPeriode($mahasiswa->mahasiswa_id, $periodeId);

        $kelasList = $this->kelasKuliahService->getByIds($kelasIds->all());

        $edomStatusByKelas = $this->edomService->getStatusesByMahasiswa($periodeId, $mahasiswa->mahasiswa_id);

        $kelasList = $kelasList->map(function ($kelas) use ($mahasiswa, $periodeId, $edomStatusByKelas, $survei) {
            $edomStatus = $edomStatusByKelas->get($kelas->kelas_id);

            if (! $edomStatus) {
                $edomStatus = $this->edomService->ensureStatus($periodeId, $mahasiswa->mahasiswa_id, $kelas->kelas_id);
            }

            // Sinkron status dari Survei (lazy) — pembeda per kelas = entitas_target.
            $edomStatus = $this->edomService->syncDariSurvei($edomStatus);

            $link = null;
            if ($survei && $edomStatus->status !== 'selesai') {
                $link = route('srv.public.welcome', [
                    'slug' => $survei->slug,
                    'entitas_target_type' => EdomStatus::class,
                    'entitas_target_id' => $edomStatus->edom_status_id,
                ]);
            }

            $kelas->edom_status = $edomStatus;
            $kelas->edom_link = $link;

            return $kelas;
        });

        return view('akademik::pages.edom.index', compact('mahasiswa', 'event', 'survei', 'kelasList'));
    }
}
