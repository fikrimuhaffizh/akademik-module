<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Http\Requests\BiodataRequest;
use Modules\Akademik\Services\BiodataService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BiodataController extends Controller
{
    public function __construct(protected BiodataService $service)
    {
        $this->middleware('permission:akd.biodata.view')->only(['index', 'data']);
        $this->middleware('permission:akd.biodata.create')->only(['create', 'store']);
        $this->middleware('permission:akd.biodata.update')->only(['edit', 'update']);
        $this->middleware('permission:akd.biodata.delete')->only(['destroy']);
    }

    public function index()
    {
        return view('akademik::pages.biodata.index');
    }

    public function data(Request $request)
    {
        $query = $this->service->getFilteredQuery($request->only(['mahasiswa_id']));

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('action', fn ($row) => view('components.ui.datatables-actions', [
                'editUrl' => route('akd.biodata.edit', encryptId($row->biodata_id)),
                'editModal' => true,
                'deleteUrl' => route('akd.biodata.destroy', encryptId($row->biodata_id)),
            ])->render())
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        return view('akademik::pages.biodata.create-edit-ajax');
    }

    public function store(BiodataRequest $request)
    {
        $this->service->create($request->validated());
        return jsonSuccess('Biodata berhasil ditambahkan.', null, ['reload' => true]);
    }

    public function edit(string $id)
    {
        $row = $this->service->findById($id);
        return view('akademik::pages.biodata.create-edit-ajax', compact('row'));
    }

    public function update(BiodataRequest $request, string $id)
    {
        $this->service->update($id, $request->validated());
        return jsonSuccess('Biodata berhasil diperbarui.', null, ['reload' => true]);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);
        return jsonSuccess('Biodata berhasil dihapus.', null, ['reload' => true]);
    }
}
