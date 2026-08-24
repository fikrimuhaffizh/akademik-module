<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Http\Requests\PeriodeAkademikRequest;
use Modules\Akademik\Services\PeriodeAkademikService;
use Modules\Akademik\Services\GeneratePenawaranService;
use Modules\Kurikulum\Services\SettingProdiService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Modules\Akademik\Models\PeriodeAkademik;
use Illuminate\Support\Facades\Log;

class PeriodeAkademikController extends Controller
{
    public function __construct(
        protected PeriodeAkademikService $service,
        protected GeneratePenawaranService $penawaranService,
        protected SettingProdiService $settingProdiService,
    ) {
        $this->middleware('permission:akd.periode-akademik.view')->only(['index', 'data']);
        $this->middleware('permission:akd.periode-akademik.create')->only(['create', 'store']);
        $this->middleware('permission:akd.periode-akademik.update')->only(['edit', 'update']);
        $this->middleware('permission:akd.periode-akademik.delete')->only(['destroy']);
    }

    public function index() { return view('akademik::pages.periode-akademik.index', ['total' => $this->service->getBaseQuery()->count()]); }

    public function data(Request $request)
    {
        return DataTables::of($this->service->getFilteredQuery($request->all()))
            ->addIndexColumn()
            ->editColumn('is_aktif', fn ($r) => $r->is_aktif ? '<span class="status status-success">Aktif</span>' : '<span class="status status-danger">Non Aktif</span>')
            ->addColumn('action', fn ($r) => view('components.ui.datatables-actions', ['editUrl' => route('akd.periode-akademik.edit', $r->encrypted_periode_akademik_id), 'editModal' => true, 'deleteUrl' => route('akd.periode-akademik.destroy', $r->encrypted_periode_akademik_id)])->render())
            ->rawColumns(['is_aktif', 'action'])->make(true);
    }

    public function create() { return view('akademik::pages.periode-akademik.create-edit-ajax'); }

    public function store(PeriodeAkademikRequest $request)
    {
        $periode = $this->service->create($request->validated());

        // Auto-generate penawaran MK untuk semua prodi yang punya kurikulum aktif
        $this->autoGeneratePenawaran($periode);

        return jsonSuccess('Periode akademik berhasil ditambahkan.', null, ['reload' => true]);
    }

    public function edit(PeriodeAkademik $periode_akademik) { return view('akademik::pages.periode-akademik.create-edit-ajax', ['item' => $periode_akademik]); }

    public function update(PeriodeAkademikRequest $request, PeriodeAkademik $periode_akademik) { $this->service->update($periode_akademik->periode_akademik_id, $request->validated()); return jsonSuccess('Periode akademik berhasil diperbarui.', null, ['reload' => true]); }

    public function destroy(PeriodeAkademik $periode_akademik) { $this->service->delete($periode_akademik->periode_akademik_id); return jsonSuccess('Periode akademik berhasil dihapus.', null, ['reload' => true]); }

    /**
     * Auto-generate penawaran MK untuk semua prodi yang punya kurikulum aktif.
     * Dipanggil saat periode baru dibuat.
     */
    protected function autoGeneratePenawaran(PeriodeAkademik $periode): void
    {
        $settingProdis = $this->settingProdiService->getAktifWithKurikulum();

        foreach ($settingProdis as $setting) {
            try {
                $result = $this->penawaranService->generate(
                    $periode->periode_akademik_id,
                    $setting->prodi_id
                );
                Log::info("Auto penawaran: prodi {$setting->prodi_id} → {$result['created']} created, {$result['skipped']} skipped");
            } catch (\Exception $e) {
                Log::warning("Auto penawaran gagal untuk prodi {$setting->prodi_id}: {$e->getMessage()}");
            }
        }
    }
}
