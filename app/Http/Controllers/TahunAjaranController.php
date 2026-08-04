<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Http\Requests\TahunAjaranRequest;
use Modules\Akademik\Services\TahunAjaranService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Modules\Akademik\Models\TahunAjaran;

class TahunAjaranController extends Controller
{
    public function __construct(protected TahunAjaranService $service)
    {
        $this->middleware('permission:akd.tahun-ajaran.view')->only(['index', 'data']);
        $this->middleware('permission:akd.tahun-ajaran.create')->only(['create', 'store']);
        $this->middleware('permission:akd.tahun-ajaran.update')->only(['edit', 'update']);
        $this->middleware('permission:akd.tahun-ajaran.delete')->only(['destroy']);
    }

    public function index() { return view('akademik::pages.tahun-ajaran.index', ['total' => $this->service->getBaseQuery()->count()]); }

    public function data(Request $request)
    {
        return DataTables::of($this->service->getFilteredQuery($request->all()))
            ->addIndexColumn()
            ->editColumn('is_aktif', fn ($r) => $r->is_aktif ? '<span class="status status-success">Aktif</span>' : '<span class="status status-danger">Non Aktif</span>')
            ->addColumn('action', fn ($r) => view('components.ui.datatables-actions', ['editUrl' => route('akd.tahun-ajaran.edit', $r->encrypted_tahun_ajaran_id), 'editModal' => true, 'deleteUrl' => route('akd.tahun-ajaran.destroy', $r->encrypted_tahun_ajaran_id)])->render())
            ->rawColumns(['is_aktif', 'action'])->make(true);
    }

    public function create() { return view('akademik::pages.tahun-ajaran.create-edit-ajax'); }

    public function store(TahunAjaranRequest $request) { $this->service->create($request->validated()); return jsonSuccess('Tahun ajaran berhasil ditambahkan.', null, ['reload' => true]); }

    public function edit(TahunAjaran $tahun_ajaran) { return view('akademik::pages.tahun-ajaran.create-edit-ajax', ['item' => $tahun_ajaran]); }

    public function update(TahunAjaranRequest $request, TahunAjaran $tahun_ajaran) { $this->service->update($tahun_ajaran->tahun_ajaran_id, $request->validated()); return jsonSuccess('Tahun ajaran berhasil diperbarui.', null, ['reload' => true]); }

    public function destroy(TahunAjaran $tahun_ajaran) { $this->service->delete($tahun_ajaran->tahun_ajaran_id); return jsonSuccess('Tahun ajaran berhasil dihapus.', null, ['reload' => true]); }
}
