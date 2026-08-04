<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Models\Mahasiswa;
use Modules\Akademik\Models\Krs;
use Modules\Akademik\Models\PeriodeAkademik;
use Modules\Akademik\Services\KrsService;
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
    ) {
        // Admin permissions
        $this->middleware('permission:akd.krs.view')->only(['index', 'data', 'monitoring']);
        $this->middleware('permission:akd.krs.update')->only(['update', 'form', 'toggle', 'ajukan']);
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
        $query = Krs::with(['mahasiswa.prodi', 'periodeAkademik'])
            ->select('akmhs_krs.*');

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
        $periodeList = PeriodeAkademik::orderByDesc('is_aktif')->orderByDesc('created_at')->get();
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
        $krs = Krs::findOrFail(decryptIdIfEncrypted($id));
        $periodeList = PeriodeAkademik::orderByDesc('is_aktif')->orderByDesc('created_at')->get();

        return view('akademik::pages.krs.create-edit-ajax', [
            'krs' => $krs,
            'periodeList' => $periodeList,
        ]);
    }

    public function update(KrsUpdateRequest $request, string $id)
    {
        $krs = Krs::findOrFail(decryptIdIfEncrypted($id));
        $this->krsService->update($krs->krs_id, $request->validated());

        return jsonSuccess('KRS berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $krs = Krs::findOrFail(decryptIdIfEncrypted($id));
        $this->krsService->delete($krs->krs_id);

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
            return Mahasiswa::where('user_id', $user->id)->first();
        }
        $sessionId = Session::get('krs_mahasiswa_id');
        if ($sessionId) {
            return Mahasiswa::find($sessionId);
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
            'pesan' => 'Pengisian KRS dibuka ' . $event->tgl_mulai->translatedFormat('d M Y') . ' s.d. ' . $event->tgl_selesai->translatedFormat('d M Y') . '.'];
    }

    protected function getRiwayatKrs(Mahasiswa $mahasiswa): array
    {
        return Krs::with('periodeAkademik')
            ->where('mahasiswa_id', $mahasiswa->mahasiswa_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(Krs $krs) => [
                'krs_id'    => $krs->encrypted_krs_id,
                'periode'   => $krs->periodeAkademik?->nama ?? '-',
                'status'    => $krs->status,
                'total_sks' => $krs->total_sks,
                'is_aktif'  => $krs->periodeAkademik?->is_aktif ?? false,
            ])->all();
    }

    /**
     * KRS mahasiswa page (search + select + form).
     */
    public function mahasiswaIndex(Request $request)
    {
        $periode = $this->periodeService->getAktif();
        $mahasiswa = $this->resolveMahasiswa();
        $isSuperadmin = auth()->user()?->hasRole('Superadmin');

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
        $mahasiswa = Mahasiswa::findOrFail(decryptIdIfEncrypted($mahasiswaId));
        $periode = $this->periodeService->getAktif();

        if (! $periode) abort(404, 'Tidak ada periode akademik aktif.');

        $prodiNama = $this->strukturService->getOrgUnitById($mahasiswa->prodi_id)?->name ?? '-';
        $krs = Krs::where('mahasiswa_id', $mahasiswa->mahasiswa_id)
            ->where('periode_akademik_id', $periode->periode_akademik_id)
            ->orderByDesc('created_at')->first();

        return view('akademik::pages.krs.form', compact('mahasiswa', 'prodiNama', 'periode', 'krs'));
    }

    public function datatable(Request $request)
    {
        $mahasiswa = Mahasiswa::findOrFail(decryptIdIfEncrypted($request->mahasiswa_id));
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
