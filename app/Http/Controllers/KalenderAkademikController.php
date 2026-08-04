<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Http\Requests\KalenderAkademikRequest;
use Modules\Akademik\Services\KalenderAkademikService;
use Modules\Akademik\Services\PeriodeAkademikService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Modules\Akademik\Models\KalenderAkademik;

class KalenderAkademikController extends Controller
{
    public function __construct(protected KalenderAkademikService $service, protected PeriodeAkademikService $periodeService)
    {
        $this->middleware('permission:akd.kalender-akademik.view')->only(['index', 'data']);
        $this->middleware('permission:akd.kalender-akademik.create')->only(['create', 'store']);
        $this->middleware('permission:akd.kalender-akademik.update')->only(['edit', 'update']);
        $this->middleware('permission:akd.kalender-akademik.delete')->only(['destroy']);
    }

    public function index()
    {
        return view('akademik::pages.kalender-akademik.index', [
            'total' => $this->service->getBaseQuery()->count(),
            'periodes' => $this->periodeService->getAll(),
        ]);
    }

    public function data(Request $request)
    {
        return DataTables::of($this->service->getFilteredQuery($request->all()))
            ->addIndexColumn()
            ->editColumn('tgl_mulai', fn ($r) => $r->tgl_mulai?->format('d M Y') ?? '-')
            ->editColumn('tgl_selesai', fn ($r) => $r->tgl_selesai?->format('d M Y') ?? '-')
            ->addColumn('periodeAkademik_nama', fn ($r) => $r->periodeAkademik?->nama ?? '-')
            ->addColumn('action', fn ($r) => view('components.ui.datatables-actions', ['editUrl' => route('akd.kalender-akademik.edit', $r->encrypted_kalender_id), 'editModal' => true, 'deleteUrl' => route('akd.kalender-akademik.destroy', $r->encrypted_kalender_id)])->render())
            ->rawColumns(['action'])->make(true);
    }

    public function create()
    {
        return view('akademik::pages.kalender-akademik.create-edit-ajax', ['periodes' => $this->periodeService->getAll()]);
    }

    public function store(KalenderAkademikRequest $request)
    {
        $this->service->create($request->validated());
        return jsonSuccess('Kalender akademik berhasil ditambahkan.', null, ['reload' => true]);
    }

    public function edit(KalenderAkademik $kalender_akademik)
    {
        return view('akademik::pages.kalender-akademik.create-edit-ajax', ['item' => $kalender_akademik, 'periodes' => $this->periodeService->getAll()]);
    }

    public function update(KalenderAkademikRequest $request, KalenderAkademik $kalender_akademik)
    {
        $this->service->update($kalender_akademik->kalender_id, $request->validated());
        return jsonSuccess('Kalender akademik berhasil diperbarui.', null, ['reload' => true]);
    }

    public function destroy(KalenderAkademik $kalender_akademik)
    {
        $this->service->delete($kalender_akademik->kalender_id);
        return jsonSuccess('Kalender akademik berhasil dihapus.', null, ['reload' => true]);
    }
}
