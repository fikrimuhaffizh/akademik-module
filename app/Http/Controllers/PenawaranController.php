<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Http\Requests\PenawaranRequest;
use Modules\Akademik\Services\GeneratePenawaranService;
use Modules\Akademik\Services\PenawaranService;
use Modules\Akademik\Services\PeriodeAkademikService;
use Modules\Kurikulum\Services\KurikulumService;
use Modules\HrCore\Services\StrukturOrganisasiService;
use Modules\Akademik\Models\PenawaranMataKuliah;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class PenawaranController extends Controller
{
    public function __construct(
        protected PenawaranService $service,
        protected PeriodeAkademikService $periodeService,
        protected GeneratePenawaranService $generatePenawaranService,
        protected KurikulumService $kurikulumService,
        protected StrukturOrganisasiService $strukturService,
    )
    {
        $this->middleware('permission:akd.penawaran.view')->only(['index', 'data', 'generateForm']);
        $this->middleware('permission:akd.penawaran.create')->only(['create', 'store', 'generateFromKurikulum']);
        $this->middleware('permission:akd.penawaran.update')->only(['edit', 'update']);
        $this->middleware('permission:akd.penawaran.delete')->only(['destroy']);
    }

    /**
     * Form Generate Penawaran dari Kurikulum.
     */
    public function generateForm()
    {
        return view('akademik::pages.penawaran.generate-form', [
            'action' => route('akd.penawaran.generate'),
            'title' => 'Generate Penawaran dari Kurikulum',
            'periodes' => $this->periodeService->getAll(),
            'prodis' => $this->strukturService->getAllProdi(),
        ]);
    }

    /**
     * Generate Penawaran dari Kurikulum (otomatis).
     */
    public function generateFromKurikulum(Request $request)
    {
        $data = $request->validate([
            'periode_akademik_id' => 'required|integer',
            'prodi_id' => 'required|integer',
        ]);

        try {
            $result = $this->generatePenawaranService->generate(
                (int) $data['periode_akademik_id'],
                (int) $data['prodi_id']
            );
        } catch (\Throwable $e) {
            logActivity('perkuliahan', 'Error Generate Penawaran MK: ' . $e->getMessage(), null);
            return jsonError('Terjadi kesalahan saat generate penawaran: ' . $e->getMessage());
        }

        if (! empty($result['errors'])) {
            return jsonError(implode(' ', $result['errors']));
        }

        $msg = "Generate selesai: {$result['created']} penawaran dibuat, {$result['skipped']} lewati (sudah ada).";

        return jsonSuccess($msg, null, ['reload' => true]);
    }

    public function index() { return view('akademik::pages.penawaran.index', ['total' => $this->service->getBaseQuery()->count()]); }

    public function data(Request $request)
    {
        return DataTables::of($this->service->getFilteredQuery($request->all()))
            ->addIndexColumn()
            ->editColumn('periodeAkademik', fn ($r) => $r->periodeAkademik?->nama ?? '-')
            ->editColumn('mata_kuliah_id', fn ($r) => $r->kurikulumMataKuliah?->mataKuliah ? ($r->kurikulumMataKuliah->mataKuliah->kode . ' - ' . $r->kurikulumMataKuliah->mataKuliah->nama) : '-')
            ->editColumn('semester', fn ($r) => $r->kurikulumMataKuliah?->semester ?? '-')
            ->editColumn('jenis', fn ($r) => $r->is_wajib
                ? '<span class="status status-info">Wajib</span>'
                : '<span class="status status-warning">Pilihan</span>' . ($r->grup_pilihan ? ' <small class="text-muted">' . e($r->grup_pilihan) . '</small>' : ''))
            ->editColumn('prodi_id', fn ($r) => $r->prodi_id ?? '-')
            ->editColumn('is_aktif', fn ($r) => $r->is_aktif ? '<span class="status status-success">Aktif</span>' : '<span class="status status-danger">Non Aktif</span>')
            ->addColumn('action', fn ($r) => view('components.ui.datatables-actions', ['editUrl' => route('akd.penawaran.edit', $r->encrypted_penawaran_id), 'editModal' => true, 'deleteUrl' => route('akd.penawaran.destroy', $r->encrypted_penawaran_id)])->render())
                ->rawColumns(['is_aktif', 'jenis', 'action'])->make(true);
    }

    public function create() { return view('akademik::pages.penawaran.create-edit-ajax', ['periodes' => $this->periodeService->getAll(), 'kurMkOptions' => $this->kurikulumService->getKurMkOptions()]); }

    public function store(PenawaranRequest $request) { $this->service->create($request->validated()); return jsonSuccess('Penawaran MK berhasil ditambahkan.', null, ['reload' => true]); }

    public function edit(PenawaranMataKuliah $penawaran) { return view('akademik::pages.penawaran.create-edit-ajax', ['item' => $penawaran, 'periodes' => $this->periodeService->getAll(), 'kurMkOptions' => $this->kurikulumService->getKurMkOptions()]); }

    public function update(PenawaranRequest $request, PenawaranMataKuliah $penawaran) { $this->service->update($penawaran->penawaran_id, $request->validated()); return jsonSuccess('Penawaran MK berhasil diperbarui.', null, ['reload' => true]); }

    public function destroy(PenawaranMataKuliah $penawaran) { $this->service->delete($penawaran->penawaran_id); return jsonSuccess('Penawaran MK berhasil dihapus.', null, ['reload' => true]); }
}
