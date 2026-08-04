<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Http\Requests\CutiRequest;
use Modules\Akademik\Services\CutiService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CutiController extends Controller
{
    public function __construct(protected CutiService $service)
    {
        $this->middleware('permission:akd.cuti.view')->only(['index', 'data']);
        $this->middleware('permission:akd.cuti.create')->only(['create', 'store']);
        $this->middleware('permission:akd.cuti.update')->only(['edit', 'update']);
        $this->middleware('permission:akd.cuti.delete')->only(['destroy']);
    }

    public function index()
    {
        return view('akademik::pages.cuti.index');
    }

    public function data(Request $request)
    {
        $query = $this->service->getFilteredQuery($request->only(['mahasiswa_id', 'status']));

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('action', fn ($row) => view('components.ui.datatables-actions', [
                'editUrl' => route('akd.cuti.edit', encryptId($row->cuti_id)),
                'editModal' => true,
                'deleteUrl' => route('akd.cuti.destroy', encryptId($row->cuti_id)),
            ])->render())
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        return view('akademik::pages.cuti.create-edit-ajax');
    }

    public function store(CutiRequest $request)
    {
        $this->service->create($request->validated());
        return jsonSuccess('Cuti berhasil ditambahkan.', null, ['reload' => true]);
    }

    public function edit(string $id)
    {
        $row = $this->service->findById($id);
        return view('akademik::pages.cuti.create-edit-ajax', compact('row'));
    }

    public function update(CutiRequest $request, string $id)
    {
        $this->service->update($id, $request->validated());
        return jsonSuccess('Cuti berhasil diperbarui.', null, ['reload' => true]);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);
        return jsonSuccess('Cuti berhasil dihapus.', null, ['reload' => true]);
    }
}
