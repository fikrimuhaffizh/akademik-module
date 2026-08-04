<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Http\Requests\TransferRequest;
use Modules\Akademik\Services\TransferService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TransferController extends Controller
{
    public function __construct(protected TransferService $service)
    {
        $this->middleware('permission:akd.transfer.view')->only(['index', 'data']);
        $this->middleware('permission:akd.transfer.create')->only(['create', 'store']);
        $this->middleware('permission:akd.transfer.update')->only(['edit', 'update']);
        $this->middleware('permission:akd.transfer.delete')->only(['destroy']);
        $this->middleware('permission:akd.transfer.approve')->only(['approve', 'reject']);
    }

    public function index()
    {
        return view('akademik::pages.transfer.index');
    }

    public function data(Request $request)
    {
        $query = $this->service->getFilteredQuery($request->only(['mahasiswa_id', 'jenis', 'status']));

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $actions = view('components.ui.datatables-actions', [
                    'editUrl' => route('akd.transfer.edit', encryptId($row->transfer_id)),
                    'editModal' => true,
                    'deleteUrl' => route('akd.transfer.destroy', encryptId($row->transfer_id)),
                ])->render();

                if ($row->status === 'diajukan' && can('akd.transfer.approve')) {
                    $actions .= ' <button class="btn btn-success btn-sm btn-approve" data-id="' . encryptId($row->transfer_id) . '" title="Setujui"><i class="ti ti-check"></i></button>';
                    $actions .= ' <button class="btn btn-danger btn-sm btn-reject" data-id="' . encryptId($row->transfer_id) . '" title="Tolak"><i class="ti ti-x"></i></button>';
                }

                return $actions;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        return view('akademik::pages.transfer.create-edit-ajax');
    }

    public function store(TransferRequest $request)
    {
        $this->service->create($request->validated());
        return jsonSuccess('Transfer berhasil ditambahkan.', null, ['reload' => true]);
    }

    public function edit(string $id)
    {
        $row = $this->service->findById($id);
        return view('akademik::pages.transfer.create-edit-ajax', compact('row'));
    }

    public function update(TransferRequest $request, string $id)
    {
        $this->service->update($id, $request->validated());
        return jsonSuccess('Transfer berhasil diperbarui.', null, ['reload' => true]);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);
        return jsonSuccess('Transfer berhasil dihapus.', null, ['reload' => true]);
    }

    /**
     * Setujui transfer.
     */
    public function approve(string $id)
    {
        $this->service->approve($id);
        return jsonSuccess('Transfer berhasil disetujui.', null, ['reload' => true]);
    }

    /**
     * Tolak transfer.
     */
    public function reject(Request $request, string $id)
    {
        $validated = $request->validate(['alasan' => 'required|string|max:1000']);
        $this->service->reject($id, $validated['alasan']);
        return jsonSuccess('Transfer ditolak.', null, ['reload' => true]);
    }
}
