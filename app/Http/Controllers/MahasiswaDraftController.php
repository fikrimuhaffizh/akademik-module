<?php
namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Akademik\Models\MahasiswaDraft;
use Modules\Akademik\Services\MahasiswaDraftService;
use Modules\HrCore\Services\StrukturOrganisasiService;
use Modules\Kurikulum\Services\KurikulumService;
use Yajra\DataTables\Facades\DataTables;

class MahasiswaDraftController extends Controller
{
    public function __construct(
        protected MahasiswaDraftService $service,
        protected StrukturOrganisasiService $strukturService,
    ) {
        $this->middleware('permission:akd.mahasiswa.view')->only(['index', 'data', 'show']);
        $this->middleware('permission:akd.mahasiswa.update')->only(['update']);
        $this->middleware('permission:akd.mahasiswa.delete')->only(['destroy', 'bulkDestroy']);
    }

    public function index()
    {
        $angkatans = $this->service->getAngkatans();
        $prodis    = $this->strukturService->getAllProdi();

        return view('akademik::pages.mahasiswa-draft.index', compact('angkatans', 'prodis'));
    }

    public function data(Request $request)
    {
        $query = $this->service->getFilteredQuery($request->only(['search', 'status_draft', 'prodi_id', 'angkatan']));
        $query->with(['prodi']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('draft_id_check', fn($row) => $row->draft_id)
            ->addColumn('no_pendaftaran', fn($row) => e($row->snapshot_json['pendaftaran']['no_pendaftaran'] ?? '-'))
            ->addColumn('nim', fn($row) => e($row->nim ?? '-'))
            ->addColumn('nama', fn($row) => '<strong>' . e($row->nama) . '</strong>')
            ->addColumn('prodi_nama', fn($row) => e($row->prodi?->name ?? '-'))
            ->addColumn('angkatan', fn($row) => e($row->angkatan))
            ->addColumn('kurikulum', fn($row) => e($row->kurikulum_kode ?? '-'))
            ->addColumn('status_badge', fn($row) => status_badge($row->status_draft))
            ->addColumn('action', function ($row) {
                $encId = encryptId($row->draft_id);

                return view('components.ui.datatables-actions', [
                    'viewUrl'       => route('akd.mahasiswa-draft.show', $encId),
                    'viewModal'     => true,
                    'viewTitle'     => 'Detail Draft',
                    'viewModalSize' => 'modal-xl',
                    'deleteUrl'     => route('akd.mahasiswa-draft.destroy', $encId),
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
        $draft            = $this->service->findById(decryptIdIfEncrypted($id));
        $kurikulumOptions = app(KurikulumService::class)->getAll();

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
            'status_draft' => ['required', 'in:draft,terima,batal'],
        ]);

        $draft = \Modules\Akademik\Models\MahasiswaDraft::findOrFail(decryptIdIfEncrypted($id));

        if ($validated['status_draft'] === 'terima' && empty($draft->kurikulum_kode)) {
            return jsonError('Draft belum ditentukan kurikulumnya. Set kurikulum terlebih dahulu sebelum menetapkan status Terima.');
        }

        $this->service->setStatusAkhir($draft->draft_id, $validated['status_draft']);

        return jsonSuccess('Status akhir draft berhasil diatur.', null, ['reload_datatable' => true]);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'nim'            => 'nullable|string|max:50',
            'nama'           => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'kurikulum_kode' => 'nullable|string|max:50',
        ]);

        $this->service->update($id, array_filter($validated));

        return jsonSuccess('Draft mahasiswa berhasil diperbarui.', null, ['reload_datatable' => true]);
    }

    public function destroy(string $id)
    {
        $this->service->delete(decryptIdIfEncrypted($id));

        return jsonSuccess('Draft berhasil dihapus.', null, ['reload_datatable' => true]);
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:akd_mahasiswa_draft,draft_id',
        ]);

        $result = $this->service->bulkDelete($validated['ids']);

        return jsonSuccess(
            sprintf('%d draft berhasil dihapus.', $result['deleted']),
            null,
            ['reload_datatable' => true],
        );
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
            'draft_ids'   => 'required|array',
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
    /**     * AJAX: return kurikulum options filtered by selected drafts' prodi + angkatan.     */
    public function kurikulumOptions(Request $request)
    {
        $request->validate(['draft_ids' => ['required', 'array', 'min:1'], 'draft_ids.*' => ['integer']]);
        $drafts = \Modules\Akademik\Models\MahasiswaDraft::whereIn('draft_id', $request->draft_ids)->get();
        if ($drafts->isEmpty()) {
            return jsonNotFound('Draft tidak ditemukan.');
        }
        $prodis = $drafts->pluck('prodi_id')->unique()->values();
        $angkatans = $drafts->pluck('angkatan')->unique()->values();
        if ($prodis->count() > 1 || $angkatans->count() > 1) {
            return jsonError('Draft terpilih memiliki program studi atau angkatan yang berbeda. Pastikan semua draft memiliki prodi dan angkatan yang sama untuk memilih kurikulum secara manual.');
        }
        $prodiId = $prodis->first();
        $angkatan = $angkatans->first();
        $prodiName = $drafts->first()->prodi?->name ?? '-';
        $kurikulum = \Modules\Kurikulum\Models\Kurikulum::where('prodi_id', $prodiId)
            ->where('is_aktif', true)
            ->orderByDesc('tahun')
            ->get();
        return jsonSuccess('Kurikulum ditemukan.', null, [
            'prodi_name' => $prodiName,
            'angkatan' => $angkatan,
            'kurikulum' => $kurikulum->map(fn($k) => [
                'kurikulum_id' => $k->kurikulum_id,
                'kode_kurikulum' => $k->kode_kurikulum,
                'nama' => $k->nama,
                'tahun' => $k->tahun,
            ]),
        ]);
    }
    /**     * Form modal Set Kurikulum massal (multi-draft).     */
    public function setKurikulumBulkForm()
    {
        $kurikulumOptions = app(KurikulumService::class)->getAll();return view('akademik::pages.mahasiswa-draft.set-kurikulum-bulk', compact('kurikulumOptions'));
    }public function setKurikulumBulk(Request $request)
    {
        $validated = $request->validate(['draft_ids' => ['required', 'array', 'min:1'], 'draft_ids.*' => ['integer'], 'mode' => ['required', 'in:auto,manual'], 'kurikulum_kode' => ['nullable', 'string', 'max:50', 'required_if:mode,manual']], ['draft_ids.required' => 'Pilih minimal satu draft (centang di kolom pertama).']);
        $result    = $this->service->setKurikulumBulk($validated['draft_ids'], $validated['mode'], $validated['kurikulum_kode'] ?? null, );
        $message   = sprintf('Kurikulum diatur untuk %d draft (%d gagal).', $result['updated'], count($result['errors']));return jsonSuccess($message, null, ['errors' => $result['errors'], 'reload_datatable' => true]);
    }
    /**     * Form modal Set Status Akhir massal (multi-draft).     */
    public function setStatusBulkForm()
    {
        return view('akademik::pages.mahasiswa-draft.set-status-bulk');
    }
    public function setStatusBulk(Request $request)
    {
        $validated = $request->validate(
            ['draft_ids' => ['required', 'array', 'min:1'], 'draft_ids.*' => ['integer'], 'status_draft' => ['required', 'in:draft,terima,batal']],
            ['draft_ids.required' => 'Pilih minimal satu draft (centang di kolom pertama).']
        );

        $drafts = MahasiswaDraft::whereIn('draft_id', $validated['draft_ids'])->get();
        $updated = 0;
        $errors = [];

        foreach ($drafts as $draft) {
            try {
                if ($validated['status_draft'] === 'terima' && empty($draft->kurikulum_kode)) {
                    $errors[] = "{$draft->nama} — belum ditentukan kurikulum.";
                    continue;
                }
                $this->service->setStatusAkhir($draft->draft_id, $validated['status_draft']);
                $updated++;
            } catch (\Throwable $e) {
                report($e);
                $errors[] = "{$draft->nama} — " . $e->getMessage();
            }
        }

        $message = sprintf('Status akhir diatur untuk %d draft.', $updated);
        if (! empty($errors)) {
            $message .= ' Gagal: ' . count($errors) . ' draft.';
        }

        return jsonSuccess($message, null, ['errors' => $errors, 'reload_datatable' => true]);
    }
}
