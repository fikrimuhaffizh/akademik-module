<?php
namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Akademik\Http\Requests\CekalRequest;
use Modules\Akademik\Services\CekalService;
use Yajra\DataTables\Facades\DataTables;

class CekalController extends Controller
{
    public function __construct(protected CekalService $service)
    {
        $this->middleware('permission:akd.cekal.view')->only(['index', 'data']);
        $this->middleware('permission:akd.cekal.create')->only(['create', 'store']);
        $this->middleware('permission:akd.cekal.update')->only(['edit', 'update']);
        $this->middleware('permission:akd.cekal.delete')->only(['destroy']);
    }

    public function index()
    {
        return view('akademik::pages.cekal.index');
    }

    public function data(Request $request)
    {
        $query = $this->service->getFilteredQuery($request->only(['jenis', 'is_aktif']));

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('action', fn($row) => view('components.ui.datatables-actions', [
                'editUrl'   => route('akd.cekal.edit', encryptId($row->cekal_id)),
                'editModal' => true,
                'deleteUrl' => route('akd.cekal.destroy', encryptId($row->cekal_id)),
            ])->render())
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        return view('akademik::pages.cekal.create-edit-ajax');
    }

    public function store(CekalRequest $request)
    {
        $this->service->create($request->validated());
        return jsonSuccess('Cekal berhasil ditambahkan.', null, ['reload' => true]);
    }

    public function edit(string $id)
    {
        $row = $this->service->findById($id);
        return view('akademik::pages.cekal.create-edit-ajax', compact('row'));
    }

    public function update(CekalRequest $request, string $id)
    {
        $this->service->update($id, $request->validated());
        return jsonSuccess('Cekal berhasil diperbarui.', null, ['reload' => true]);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);
        return jsonSuccess('Cekal berhasil dihapus.', null, ['reload' => true]);
    }
}
