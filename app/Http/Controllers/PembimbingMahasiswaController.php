<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Http\Requests\PembimbingMahasiswaRequest;
use Modules\Akademik\Services\PembimbingMahasiswaService;
use Modules\Akademik\Services\PeriodeAkademikService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Modules\Akademik\Models\PembimbingMahasiswa;
use Modules\Referensi\Services\SysRefService;

class PembimbingMahasiswaController extends Controller
{
    public function __construct(protected PembimbingMahasiswaService $service, protected PeriodeAkademikService $periodeService, protected SysRefService $sysRefService)
    {
        $this->middleware('permission:akd.pembimbing-mahasiswa.view')->only(['index', 'data']);
        $this->middleware('permission:akd.pembimbing-mahasiswa.create')->only(['create', 'store']);
        $this->middleware('permission:akd.pembimbing-mahasiswa.update')->only(['edit', 'update']);
        $this->middleware('permission:akd.pembimbing-mahasiswa.delete')->only(['destroy']);
    }

    public function index()
    {
        return view('akademik::pages.pembimbing-mahasiswa.index', [
            'total' => $this->service->getBaseQuery()->count(),
        ]);
    }

    public function data(Request $request)
    {
        return DataTables::of($this->service->getFilteredQuery($request->all()))
            ->addIndexColumn()
            ->editColumn('periode_akademik_id', fn ($r) => $r->periodeAkademik?->nama ?? '-')
            ->editColumn('pegawai_id', fn ($r) => $r->pegawai?->nama ?? '-')
            ->editColumn('mahasiswa_id', fn ($r) => $r->mahasiswa ? ($r->mahasiswa->nim . ' - ' . $r->mahasiswa->nama) : '-')
            ->editColumn('jenis_pembimbing', fn ($r) => $r->jenisPembimbing?->label ?? '-')
            ->addColumn('action', fn ($r) => view('components.ui.datatables-actions', ['editUrl' => route('akd.pembimbing-mahasiswa.edit', $r->encrypted_pma_id), 'editModal' => true, 'deleteUrl' => route('akd.pembimbing-mahasiswa.destroy', $r->encrypted_pma_id)])->render())
            ->rawColumns(['action'])->make(true);
    }

    public function create()
    {
        return view('akademik::pages.pembimbing-mahasiswa.create-edit-ajax', [
            'periodes' => $this->periodeService->getAll(),
            'jenisPembimbing' => $this->getJenisPembimbing(),
        ]);
    }

    public function store(PembimbingMahasiswaRequest $request)
    {
        $this->service->create($request->validated());
        return jsonSuccess('Pembimbing mahasiswa berhasil ditambahkan.', null, ['reload' => true]);
    }

    public function edit(PembimbingMahasiswa $pembimbing_mahasiswa)
    {
        return view('akademik::pages.pembimbing-mahasiswa.create-edit-ajax', [
            'item' => $pembimbing_mahasiswa,
            'periodes' => $this->periodeService->getAll(),
            'jenisPembimbing' => $this->getJenisPembimbing(),
        ]);
    }

    public function update(PembimbingMahasiswaRequest $request, PembimbingMahasiswa $pembimbing_mahasiswa)
    {
        $this->service->update($pembimbing_mahasiswa->pma_id, $request->validated());
        return jsonSuccess('Pembimbing mahasiswa berhasil diperbarui.', null, ['reload' => true]);
    }

    public function destroy(PembimbingMahasiswa $pembimbing_mahasiswa)
    {
        $this->service->delete($pembimbing_mahasiswa->pma_id);
        return jsonSuccess('Pembimbing mahasiswa berhasil dihapus.', null, ['reload' => true]);
    }

    private function getJenisPembimbing()
    {
        return $this->sysRefService->getActiveByGrup('pembimbing_mahasiswa');
    }
}
