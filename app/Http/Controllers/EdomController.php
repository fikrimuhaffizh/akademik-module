<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Akademik\Models\Mahasiswa;
use Modules\Akademik\Services\EdomService;
use Modules\Akademik\Services\KelasKuliahService;
use Modules\Survei\Models\Survei\Survei;
use Modules\Survei\Services\Survei\SurveiService;
use Yajra\DataTables\DataTables;

class EdomController extends Controller
{
    public function __construct(
        protected EdomService $edomService,
        protected KelasKuliahService $kelasKuliahService,
        protected SurveiService $surveiService,
    )
    {
        $this->middleware('permission:akd.edom.view')->only(['adminIndex', 'adminData', 'rekap']);
        $this->middleware('permission:akd.edom.create')->only(['generate']);
        $this->middleware('permission:akd.edom.update')->only(['activate', 'close']);
    }

    /**
     * Admin: Index — rekap progress EDOM per kelas
     */
    public function adminIndex()
    {
        $periodeAktif = $this->edomService->getActivePeriode();
        $stats = $periodeAktif ? $this->edomService->getStats($periodeAktif->periode_akademik_id) : null;

        return view('akademik::pages.edom.index', compact('periodeAktif', 'stats'));
    }

    /**
     * Admin: DataTables data
     */
    public function adminData(Request $request)
    {
        $periodeAktif = $this->edomService->getActivePeriode();
        if (!$periodeAktif) {
            return DataTables::of(collect())->make(true);
        }

        $rekap = $this->edomService->getRekapByKelas($periodeAktif->periode_akademik_id);

        return DataTables::of($rekap)
            ->addIndexColumn()
            ->editColumn('persentase', function ($r) {
                $class = match (true) {
                    $r['persentase'] >= 80 => 'bg-green-lt text-green',
                    $r['persentase'] >= 50 => 'bg-yellow-lt text-yellow',
                    default => 'bg-red-lt text-red',
                };
                return '<div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height: 6px;">
                        <div class="progress-bar ' . $class . '" style="width: ' . $r['persentase'] . '%"></div>
                    </div>
                    <span class="badge ' . $class . '">' . $r['persentase'] . '%</span>
                </div>';
            })
            ->editColumn('selesai', fn ($r) => '<span class="text-green fw-bold">' . $r['selesai'] . '</span> / ' . $r['total'])
            ->editColumn('rata_rata', function ($r) {
                if ($r['rata_rata'] === null) {
                    return '<span class="text-secondary">-</span>';
                }
                $score = $r['rata_rata'];
                $class = $score >= 4 ? 'bg-green-lt text-green' : ($score >= 3 ? 'bg-yellow-lt text-yellow' : 'bg-red-lt text-red');
                return '<span class="badge ' . $class . '">' . number_format($score, 2) . '</span>';
            })
            ->addColumn('action', function ($r) {
                return '<a href="' . route('akd.edom.rekap', $r['kelas_id']) . '" class="btn btn-sm btn-ghost-primary">Detail</a>';
            })
            ->rawColumns(['persentase', 'selesai', 'rata_rata', 'action'])->make(true);
    }

    /**
     * Admin: Generate edom_status dari KRS
     */
    public function generate(int $periodeAkademikId)
    {
        $created = $this->edomService->generateForPeriode($periodeAkademikId);

        return jsonSuccess("Berhasil generate {$created} data EDOM.", null, ['reload' => true]);
    }

    /**
     * Admin: Aktifkan EDOM per kelas
     */
    public function activate($edomKelas)
    {
        // Placeholder — akan diimplementasikan setelah akper_edom_kelas diaktifkan
        return jsonSuccess('EDOM berhasil diaktifkan.', null, ['reload' => true]);
    }

    /**
     * Admin: Tutup EDOM per kelas
     */
    public function close($edomKelas)
    {
        return jsonSuccess('EDOM berhasil ditutup.', null, ['reload' => true]);
    }

    /**
     * Admin: Rekap detail per kelas
     */
    public function rekap($kelasId)
    {
        $periodeAktif = $this->edomService->getActivePeriode();
        if (!$periodeAktif) {
            abort(404, 'Tidak ada periode EDOM aktif.');
        }

        $statuses = $this->edomService->getStatusesForRekap($periodeAktif->periode_akademik_id, $kelasId);

        $kelas = $this->kelasKuliahService->findWithDetail($kelasId);

        return view('akademik::pages.edom.rekap', compact('statuses', 'kelas', 'periodeAktif'));
    }

    /**
     * Mahasiswa: Halaman EDOM saya
     */
    public function mahasiswaIndex()
    {
        $user = auth()->user();
        $periodeAktif = $this->edomService->getActivePeriode();

        $list = null;
        if ($periodeAktif) {
            // resolve relasi user -> mahasiswa yang
            // benar. Sebelumnya memakai $user->id sebagai mahasiswa_id
            // (placeholder) sehingga daftar EDOM mahasiswa selalu kosong.
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if ($mahasiswa) {
                $list = $this->edomService->getListForMahasiswa($mahasiswa->mahasiswa_id, $periodeAktif->periode_akademik_id);
            }
        }

        return view('akademik::pages.edom.mahasiswa', compact('periodeAktif', 'list'));
    }

    /**
     * Mahasiswa: Mulai isi EDOM → redirect ke Survei module.
     */
    public function mulaiIsi($edomStatusId)
    {
        $user = auth()->user();
        $edomStatus = $this->edomService->mulaiIsi($edomStatusId, $user->id);

        // Dapatkan konfigurasi EDOM kelas dari database
        $edomKelas = $this->edomService->findKelasConfig($edomStatus->kelas_id, $edomStatus->periode_akademik_id);

        if (!$edomKelas || !$edomKelas->survei_id) {
            abort(404, 'Konfigurasi EDOM untuk kelas ini belum tersedia.');
        }

        // Dapatkan slug survei
        $survei = $this->surveiService->findById($edomKelas->survei_id);
        if (!$survei) {
            abort(404, 'Survei EDOM tidak ditemukan.');
        }

        // Redirect ke halaman pengisian survei
        return redirect()->route('srv.public.start', [
            'slug' => $survei->slug,
            'edom_status_id' => $edomStatus->edom_status_id,
        ]);
    }
}
