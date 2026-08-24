<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Models\Mahasiswa;
use Modules\Akademik\Models\PeriodeAkademik;
use Modules\Akademik\Services\KrsService;
use Modules\Akademik\Services\MahasiswaService;
use Modules\Akademik\Services\PeriodeAkademikService;
use Modules\Akademik\Services\KalenderAkademikService;
use Modules\HrCore\Services\StrukturOrganisasiService;
use Illuminate\Http\Request;
use Modules\Akademik\Http\Requests\KrsStoreRequest;
use Modules\Akademik\Http\Requests\KrsUpdateRequest;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;

class KrsController extends Controller
{
    public function __construct(
        protected KrsService $krsService,
        protected PeriodeAkademikService $periodeService,
        protected KalenderAkademikService $kalenderService,
        protected StrukturOrganisasiService $strukturService,
        protected MahasiswaService $mahasiswaService,
    ) {
        // Admin permissions
        // create/store/edit/datatable/pilih/
        // mahasiswaIndex sebelumnya tanpa gate -> KRS siapa pun bisa
        // dibuat/dibaca/di-set session-nya.
        $this->middleware('permission:akd.krs.view')->only(['index', 'data', 'monitoring', 'mahasiswaIndex', 'datatable']);
        $this->middleware('permission:akd.krs.create')->only(['create', 'store', 'pilih']);
        $this->middleware('permission:akd.krs.update')->only(['update', 'form', 'toggle', 'ajukan', 'edit']);
        $this->middleware('permission:akd.krs.delete')->only(['destroy']);
    }

    // ═══════════════════════════════════════════════════════════
    // ADMIN: DataTable + resource CRUD
    // ═══════════════════════════════════════════════════════════

    /**
     * Admin KRS list page (DataTable of all KRS).
     */
    public function index(Request $request)
    {
        return view('akademik::pages.krs.admin-index');
    }

    /**
     * DataTables source for admin KRS list.
     */
    public function data(Request $request)
    {
        $query = $this->krsService->getAdminQuery();

        return Datatables::of($query)
            ->editColumn('mahasiswa_id', fn($row) => $row->mahasiswa?->nama ?? '-')
            ->editColumn('periode_akademik_id', fn($row) => $row->periodeAkademik?->nama ?? '-')
            ->editColumn('status', fn($row) => status_badge($row->status))
            ->editColumn('total_sks', fn($row) => '<strong>' . $row->total_sks . '</strong>')
            ->addColumn('action', function ($row) {
                $editUrl = route('akd.krs.edit', $row->encrypted_krs_id);
                $deleteUrl = route('akd.krs.destroy', $row->encrypted_krs_id);
                return view('components.ui.dropdown', [
                    'trigger' => '<i class="ti ti-dots-vertical"></i>',
                    'items' => [
                        ['type' => 'edit', 'url' => $editUrl],
                        ['type' => 'delete', 'url' => $deleteUrl],
                    ],
                ])->render();
            })
            ->rawColumns(['status', 'total_sks', 'action'])
            ->make(true);
    }

    /**
     * Create KRS (admin manual entry).
     */
    public function create()
    {
        $periodeList = $this->periodeService->getList();
        return view('akademik::pages.krs.create-edit-ajax', [
            'krs' => null,
            'periodeList' => $periodeList,
        ]);
    }

    public function store(KrsStoreRequest $request)
    {
        $this->krsService->create([
            'mahasiswa_id'        => decryptIdIfEncrypted($request->mahasiswa_id),
            'periode_akademik_id' => $request->periode_akademik_id,
            'kelas_ids'           => array_map('decryptIdIfEncrypted', $request->kelas_ids),
        ]);

        return jsonSuccess('KRS berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $krs = $this->krsService->findById($id);
        $periodeList = $this->periodeService->getList();

        return view('akademik::pages.krs.create-edit-ajax', [
            'krs' => $krs,
            'periodeList' => $periodeList,
        ]);
    }

    public function update(KrsUpdateRequest $request, string $id)
    {
        $krs = $this->krsService->findById($id);
        $this->krsService->update($krs->krs_id, $request->validated());

        return jsonSuccess('KRS berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $this->krsService->delete(decryptIdIfEncrypted($id));

        return jsonSuccess('KRS berhasil dihapus.');
    }

    /**
     * Monitoring: rekap KRS per periode (admin view).
     */
    public function monitoring(Request $request)
    {
        $periode = $this->periodeService->getAktif();
        $data = $periode ? $this->krsService->getMonitoring($periode->periode_akademik_id) : collect();

        return view('akademik::pages.krs.monitoring', compact('periode', 'data'));
    }

    // ═══════════════════════════════════════════════════════════
    // MAHASISWA: KRS pengisian
    // ═══════════════════════════════════════════════════════════

    protected function resolveMahasiswa(): ?Mahasiswa
    {
        $user = auth()->user();
        if ($user && $user->hasRole('Mahasiswa')) {
            return $this->mahasiswaService->getByUserId($user->id);
        }
        $sessionId = Session::get('krs_mahasiswa_id');
        if ($sessionId) {
            return $this->mahasiswaService->findByIdRaw($sessionId);
        }
        return null;
    }

    protected function getBanner(PeriodeAkademik $periode, ?Mahasiswa $mahasiswa): array
    {
        $event = $this->kalenderService->getKrsEvent($periode->periode_akademik_id);

        if (! $event) {
            return ['status' => 'berakhir', 'tgl_mulai' => null, 'tgl_selesai' => null,
                'pesan' => 'Tidak ada jadwal pengisian KRS aktif.'];
        }

        return ['status' => 'aktif', 'tgl_mulai' => $event->tgl_mulai, 'tgl_selesai' => $event->tgl_selesai,
            'pesan' => 'Pengisian KRS dibuka ' . formatTanggalIndo($event->tgl_mulai) . ' s.d. ' . formatTanggalIndo($event->tgl_selesai) . '.'];
    }

    protected function getRiwayatKrs(Mahasiswa $mahasiswa): array
    {
        return $this->krsService->getRiwayatByMahasiswa($mahasiswa);
    }

    /**
     * KRS mahasiswa page (search + select + form).
     */
    public function mahasiswaIndex(Request $request)
    {
        $periode = $this->periodeService->getAktif();
        $mahasiswa = $this->resolveMahasiswa();
        $isSuperadmin = hasRole('Superadmin');

        if ($request->has('clear')) {
            Session::forget('krs_mahasiswa_id');
            $mahasiswa = null;
        }

        if (! $periode) {
            return view('akademik::pages.krs.index', [
                'periode' => null, 'mahasiswa' => null, 'banner' => null, 'riwayat' => [], 'isSuperadmin' => $isSuperadmin,
            ]);
        }

        $banner = $mahasiswa ? $this->getBanner($periode, $mahasiswa) : null;
        $riwayat = $mahasiswa ? $this->getRiwayatKrs($mahasiswa) : [];

        return view('akademik::pages.krs.index', compact('periode', 'mahasiswa', 'banner', 'riwayat', 'isSuperadmin'));
    }

    public function pilih(Request $request)
    {
        $request->validate(['mahasiswa_id' => 'required']);
        Session::put('krs_mahasiswa_id', decryptIdIfEncrypted($request->mahasiswa_id));
        return redirect()->route('akd.krs-mahasiswa.index');
    }

    public function form(string $mahasiswaId)
    {
        $mahasiswa = $this->mahasiswaService->findById($mahasiswaId);
        $periode = $this->periodeService->getAktif();

        if (! $periode) abort(404, 'Tidak ada periode akademik aktif.');

        $prodiNama = $this->strukturService->getOrgUnitById($mahasiswa->prodi_id)?->name ?? '-';
        $krs = $this->krsService->findByMahasiswaPeriode($mahasiswa->mahasiswa_id, $periode->periode_akademik_id);

        return view('akademik::pages.krs.form', compact('mahasiswa', 'prodiNama', 'periode', 'krs'));
    }

    public function datatable(Request $request)
    {
        $mahasiswa = $this->mahasiswaService->findById($request->mahasiswa_id);
        $periode = $this->periodeService->getAktif();

        if (! $periode) return response()->json(['data' => []]);

        $rows = $this->krsService->getMkKelasPenawaranMhs(
            $mahasiswa->mahasiswa_id, $periode->periode_akademik_id
        );

        return response()->json(['data' => $rows->values()]);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'mahasiswa_id' => 'required', 'kelas_id' => 'required', 'ambil' => 'required|boolean',
        ]);

        $mahasiswaId = decryptIdIfEncrypted($request->mahasiswa_id);
        $kelasId = decryptIdIfEncrypted($request->kelas_id);
        $periode = $this->periodeService->getAktif();

        if (! $periode) return jsonError('Tidak ada periode akademik aktif.', 404);

        try {
            $krs = $this->krsService->toggleKelas($mahasiswaId, $periode->periode_akademik_id, (int) $kelasId, (bool) $request->ambil);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return jsonError($e->errors()['krs'][0] ?? 'Gagal menyimpan KRS.', 422);
        }

        return jsonSuccess('Berhasil.', null, [
            'krs_id' => encryptId($krs->krs_id), 'encrypted_krs_id' => $krs->encrypted_krs_id,
            'status' => $krs->status, 'total_sks' => $krs->total_sks,
        ]);
    }

    public function ajukan(Request $request)
    {
        $request->validate(['krs_id' => 'required']);

        try {
            $krs = $this->krsService->ajukan(decryptIdIfEncrypted($request->krs_id));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return jsonError($e->errors()['krs'][0] ?? 'Gagal mengajukan KRS.', 422);
        }

        return jsonSuccess('KRS berhasil diajukan.', null, ['status' => $krs->status, 'total_sks' => $krs->total_sks]);
    }
}
