<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Services\MahasiswaDraftService;
use Modules\HrCore\Services\StrukturOrganisasiService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class MahasiswaDraftController extends Controller
{
    public function __construct(
        protected MahasiswaDraftService $service,
        protected StrukturOrganisasiService $strukturService,
    ) {
        $this->middleware('permission:akd.mahasiswa.view')->only(['index', 'data', 'show']);
        $this->middleware('permission:akd.mahasiswa.update')->only(['update']);
    }

    public function index()
    {
        $prodis = $this->strukturService->getAllProdi();

        return view('akademik::pages.mahasiswa-draft.index', compact('prodis'));
    }

    public function data(Request $request)
    {
        $query = $this->service->getFilteredQuery($request->only(['search', 'status_draft', 'prodi_id', 'angkatan']));
        $query->with(['prodi']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('draft_id_check', fn ($row) => $row->draft_id)
            ->addColumn('nim', fn ($row) => e($row->nim ?? '-'))
            ->addColumn('nama', fn ($row) => '<strong>' . e($row->nama) . '</strong>')
            ->addColumn('prodi_nama', fn ($row) => e($row->prodi?->name ?? '-'))
            ->addColumn('angkatan', fn ($row) => e($row->angkatan))
            ->addColumn('kurikulum', fn ($row) => e($row->kurikulum_kode ?? '-'))
            ->addColumn('status_badge', fn ($row) => status_badge($row->status_draft))
            ->addColumn('action', function ($row) {
                return view('components.ui.datatables-actions', [
                    'viewUrl' => route('akd.mahasiswa-draft.show', $row->draft_id),
                ])->render();
            })
            ->rawColumns(['nama', 'status_badge', 'action'])
            ->make(true);
    }

    public function show(string $id)
    {
        $draft = $this->service->findById($id);

        abort_unless($draft, 404);

        return view('akademik::pages.mahasiswa-draft.show', compact('draft'));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'nim' => 'nullable|string|max:50',
            'nama' => 'nullable|string|max:255',
            'kurikulum_kode' => 'nullable|string|max:50',
        ]);

        $this->service->update($id, array_filter($validated));

        return redirect()->route('akd.mahasiswa-draft.show', $id)
            ->with('success', 'Draft mahasiswa berhasil diperbarui.');
    }

    public function sync(Request $request)
    {
        $result = $this->service->syncFromPmb();

        $message = sprintf('Sync selesai: %d disync, %d dilewati.', $result['synced'], $result['skipped']);
        if (! empty($result['errors'])) {
            $message .= ' Error: ' . implode('; ', $result['errors']);
        }

        return redirect()->route('akd.mahasiswa-draft.index')
            ->with($result['errors'] ? 'error' : 'success', $message);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'draft_ids' => 'required|array',
            'draft_ids.*' => 'integer|exists:akd_mahasiswa_draft,draft_id',
        ]);

        $result = $this->service->submit($validated['draft_ids']);

        $message = sprintf('Submit selesai: %d berhasil.', $result['submitted']);
        if (! empty($result['errors'])) {
            $message .= ' Error: ' . count($result['errors']) . ' draft gagal.';
        }

        return redirect()->route('akd.mahasiswa-draft.index')
            ->with($result['errors'] ? 'error' : 'success', $message);
    }
}
