<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Http\Requests\RuangKuliahRequest;
use Modules\Akademik\Services\RuangKuliahService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Modules\Akademik\Models\RuangKuliah;

class RuangKuliahController extends Controller
{
    public function __construct(protected RuangKuliahService $service)
    {
        $this->middleware('permission:akd.ruang-akd.view')->only(['index', 'data']);
        $this->middleware('permission:akd.ruang-akd.create')->only(['create', 'store']);
        $this->middleware('permission:akd.ruang-akd.update')->only(['edit', 'update']);
        $this->middleware('permission:akd.ruang-akd.delete')->only(['destroy']);
    }

    public function index() { return view('akademik::pages.ruang-akd.index', ['total' => $this->service->getBaseQuery()->count()]); }

    public function data(Request $request)
    {
        return DataTables::of($this->service->getFilteredQuery($request->all()))
            ->addIndexColumn()
            ->editColumn('kapasitas', fn ($r) => '<span class="badge bg-blue-lt text-blue">' . $r->kapasitas . '</span>')
            ->editColumn('is_aktif', fn ($r) => $r->is_aktif ? '<span class="status status-success">Aktif</span>' : '<span class="status status-danger">Non Aktif</span>')
            ->addColumn('action', fn ($r) => view('components.ui.datatables-actions', ['editUrl' => route('akd.ruang-akd.edit', $r->encrypted_ruang_id), 'editModal' => true, 'deleteUrl' => route('akd.ruang-akd.destroy', $r->encrypted_ruang_id)])->render())
            ->rawColumns(['kapasitas', 'is_aktif', 'action'])->make(true);
    }

    public function create() { return view('akademik::pages.ruang-akd.create-edit-ajax'); }

    public function store(RuangKuliahRequest $request) { $this->service->create($request->validated()); return jsonSuccess('Ruang kuliah berhasil ditambahkan.', null, ['reload' => true]); }

    public function edit(RuangKuliah $ruang_kuliah) { return view('akademik::pages.ruang-akd.create-edit-ajax', ['item' => $ruang_kuliah]); }

    public function update(RuangKuliahRequest $request, RuangKuliah $ruang_kuliah) { $this->service->update($ruang_kuliah->ruang_id, $request->validated()); return jsonSuccess('Ruang kuliah berhasil diperbarui.', null, ['reload' => true]); }

    public function destroy(RuangKuliah $ruang_kuliah) { $this->service->delete($ruang_kuliah->ruang_id); return jsonSuccess('Ruang kuliah berhasil dihapus.', null, ['reload' => true]); }
}
