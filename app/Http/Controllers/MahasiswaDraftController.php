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
        $angkatans = $this->service->getAngkatans();
        $prodis = $this->strukturService->getAllProdi();

        return view('akademik::pages.mahasiswa-draft.index', compact('angkatans', 'prodis'));
    }

    public function data(Request $request)
    {
        $query = $this->service->getFilteredQuery($request->only(['search', 'status_draft', 'prodi_id', 'angkatan']));
        $query->with(['prodi']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('draft_id_check', fn ($row) => $row->draft_id)
            ->addColumn('no_pendaftaran', fn ($row) => e($row->snapshot_json['pendaftaran']['no_pendaftaran'] ?? '-'))
            ->addColumn('nim', fn ($row) => e($row->nim ?? '-'))
            ->addColumn('nama', fn ($row) => '<strong>' . e($row->nama) . '</strong>')
            ->addColumn('prodi_nama', fn ($row) => e($row->prodi?->name ?? '-'))
            ->addColumn('angkatan', fn ($row) => e($row->angkatan))
            ->addColumn('kurikulum', fn ($row) => e($row->kurikulum_kode ?? '-'))
            ->addColumn('status_badge', fn ($row) => status_badge($row->status_draft))
            ->addColumn('action', function ($row) {
                $encId = encryptId($row->draft_id);

                return view('components.ui.datatables-actions', [
                    'viewUrl'      => route('akd.mahasiswa-draft.show', $row->draft_id),
                    'viewModal'    => true,
                    'viewTitle'    => 'Detail Draft',
                    'viewModalSize'=> 'modal-xl',
                    'extraActions' => [
                        [
                            'label'     => 'Set Kurikulum',
                            'icon'      => 'book-2',
                            'modal'     => true,
                            'url'       => route('akd.mahasiswa-draft.set-kurikulum.form', $encId),
                            'modalSize' => 'modal-lg',
                        ],
                        [
                            'label'     => 'Set Status Akhir',
                            'icon'      => 'flag-check',
                            'modal'     => true,
                            'url'       => route('akd.mahasiswa-draft.set-status.form', $encId),
                            'modalSize' => 'modal-sm',
                        ],
                    ],
                ])->render();
            })
            ->rawColumns(['nama', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * Detail draft — konten modal bertab (Akademik | Biodata | PMB).
     */
    public function show(string $id)
    {
        $draft = $this->service->findById(decryptIdIfEncrypted($id));

        abort_unless($draft, 404);

        return view('akademik::pages.mahasiswa-draft.show-modal', ['draft' => $draft]);
    }

    /**
     * Form modal Set Kurikulum.
     */
    public function setKurikulumForm(string $id)
    {
        $draft = $this->service->findById(decryptIdIfEncrypted($id));
        $kurikulumOptions = app(\Modules\Kurikulum\Services\KurikulumService::class)->getAll();

        return view('akademik::pages.mahasiswa-draft.set-kurikulum', compact('draft', 'kurikulumOptions'));
    }

    public function setKurikulum(Request $request, string $id)
    {
        $validated = $request->validate([
            'kurikulum_kode' => ['nullable', 'string', 'max:50'],
        ]);

        $this->service->update(decryptIdIfEncrypted($id), ['kurikulum_kode' => $validated['kurikulum_kode'] ?? null]);

        return jsonSuccess('Kurikulum draft berhasil diatur.', null, ['reload_datatable' => true]);
    }

    /**
     * Form modal Set Status Akhir.
     */
    public function setStatusForm(string $id)
    {
        $draft = $this->service->findById(decryptIdIfEncrypted($id));

        return view('akademik::pages.mahasiswa-draft.set-status', ['draft' => $draft]);
    }

    public function setStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'status_draft' => ['required', 'in:draft,submitted,batal'],
        ]);

        $this->service->setStatusAkhir(decryptIdIfEncrypted($id), $validated['status_draft']);

        return jsonSuccess('Status akhir draft berhasil diatur.', null, ['reload_datatable' => true]);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'nim' => 'nullable|string|max:50',
            'nama' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'kurikulum_kode' => 'nullable|string|max:50',
        ]);

        $this->service->update($id, array_filter($validated));

        return jsonSuccess('Draft mahasiswa berhasil diperbarui.', null, ['reload_datatable' => true]);
    }

    public function sync(Request $request)
    {
        $result = $this->service->syncFromPmb();

        $message = sprintf('Sync selesai: %d ditambahkan, %d dilewati (sudah ada), %d gagal.',
            $result['synced'], $result['skipped'], count($result['errors']));

        return jsonSuccess($message, null, [
            'synced'           => $result['synced'],
            'skipped'          => $result['skipped'],
            'errors'           => $result['errors'],
            'reload_datatable' => true,
        ]);
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
    /**     * Form modal Set Kurikulum massal (multi-draft).     */    public function setKurikulumBulkForm()    {        $kurikulumOptions = app(\Modules\Kurikulum\Services\KurikulumService::class)->getAll();        return view('akademik::pages.mahasiswa-draft.set-kurikulum-bulk', compact('kurikulumOptions'));    }    public function setKurikulumBulk(Request $request)    {        $validated = $request->validate([            'draft_ids'   => ['required', 'array', 'min:1'],            'draft_ids.*' => ['integer'],            'mode'        => ['required', 'in:auto,manual'],            'kurikulum_kode' => ['nullable', 'string', 'max:50', 'required_if:mode,manual'],        ], [            'draft_ids.required' => 'Pilih minimal satu draft (centang di kolom pertama).',        ]);        $result = $this->service->setKurikulumBulk(            $validated['draft_ids'],            $validated['mode'],            $validated['kurikulum_kode'] ?? null,        );        $message = sprintf('Kurikulum diatur untuk %d draft (%d gagal).',            $result['updated'], count($result['errors']));        return jsonSuccess($message, null, [            'errors'           => $result['errors'],            'reload_datatable' => true,        ]);    }    /**     * Form modal Set Status Akhir massal (multi-draft).     */    public function setStatusBulkForm()    {        return view('akademik::pages.mahasiswa-draft.set-status-bulk');    }    public function setStatusBulk(Request $request)    {        $validated = $request->validate([            'draft_ids'   => ['required', 'array', 'min:1'],            'draft_ids.*' => ['integer'],            'status_draft' => ['required', 'in:draft,submitted,batal'],        ], [            'draft_ids.required' => 'Pilih minimal satu draft (centang di kolom pertama).',        ]);        $updated = 0;        foreach ($validated['draft_ids'] as $id) {            try {                $this->service->setStatusAkhir($id, $validated['status_draft']);                $updated++;            } catch (\Throwable $e) {                report($e);            }        }        return jsonSuccess("Status akhir diatur untuk {$updated} draft.", null, ['reload_datatable' => true]);    }}