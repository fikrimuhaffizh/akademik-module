<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Http\Requests\RiwayatStatusRequest;
use Modules\Akademik\Services\RiwayatStatusService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RiwayatStatusController extends Controller
{
    public function __construct(protected RiwayatStatusService $service)
    {
        $this->middleware('permission:akd.riwayat-status.view')->only(['index', 'data']);
        $this->middleware('permission:akd.riwayat-status.create')->only(['create', 'store']);
        $this->middleware('permission:akd.riwayat-status.update')->only(['edit', 'update']);
        $this->middleware('permission:akd.riwayat-status.delete')->only(['destroy']);
    }

    public function index()
    {
        return view('akademik::pages.riwayat-status.index');
    }

    public function data(Request $request)
    {
        $query = $this->service->getFilteredQuery($request->only(['mahasiswa_id', 'status_baru']));

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('action', fn ($row) => view('components.ui.datatables-actions', [
                'editUrl' => route('akd.riwayat-status.edit', encryptId($row->riwayat_status_id)),
                'editModal' => true,
                'deleteUrl' => route('akd.riwayat-status.destroy', encryptId($row->riwayat_status_id)),
            ])->render())
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        return view('akademik::pages.riwayat-status.create-edit-ajax');
    }

    public function store(RiwayatStatusRequest $request)
    {
        $this->service->create($request->validated());
        return jsonSuccess('Riwayat status berhasil ditambahkan.', null, ['reload' => true]);
    }

    public function edit(string $id)
    {
        $row = $this->service->findById($id);
        return view('akademik::pages.riwayat-status.create-edit-ajax', compact('row'));
    }

    public function update(RiwayatStatusRequest $request, string $id)
    {
        $this->service->update($id, $request->validated());
        return jsonSuccess('Riwayat status berhasil diperbarui.', null, ['reload' => true]);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);
        return jsonSuccess('Riwayat status berhasil dihapus.', null, ['reload' => true]);
    }
}
