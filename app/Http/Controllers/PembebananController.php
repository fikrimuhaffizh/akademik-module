<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Http\Requests\PembebananRequest;
use Modules\Akademik\Models\PembebananDosen;
use Modules\Akademik\Services\PembebananService;
use Modules\Akademik\Services\KelasKuliahService;
use Modules\HrCore\Services\PegawaiService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class PembebananController extends Controller
{
    public function __construct(
        protected PembebananService $service,
        protected PegawaiService $pegawaiService,
        protected KelasKuliahService $kelasService,
    ) {
        $this->middleware('permission:akd.pembebanan.view')->only(['index', 'data']);
        $this->middleware('permission:akd.pembebanan.create')->only(['create', 'store']);
        $this->middleware('permission:akd.pembebanan.update')->only(['edit', 'update']);
        $this->middleware('permission:akd.pembebanan.delete')->only(['destroy']);
    }

    public function index()
    {
        return view('akademik::pages.pembebanan.index', [
            'total' => $this->service->getBaseQuery()->count(),
        ]);
    }

    public function data(Request $request)
    {
        return DataTables::of($this->service->getFilteredQuery($request->all()))
            ->addIndexColumn()
            ->addColumn('kelas', function ($r) {
                $namaKelas = $r->kelas?->nama_kelas;
                $namaMataKuliah = $r->kelas?->penawaran?->kurikulumMataKuliah?->mataKuliah?->nama;

                if (! $namaKelas) {
                    return '-';
                }

                return $namaMataKuliah ? "{$namaKelas} - {$namaMataKuliah}" : $namaKelas;
            })
            ->addColumn('pegawai', function ($r) {
                $pegawai = $this->pegawaiService->findById($r->pegawai_id);

                return $pegawai ? $pegawai->nama : '-';
            })
            ->editColumn('peran', fn ($r) => '<span class="badge bg-blue-lt text-blue">' . ucfirst($r->peran) . '</span>')
            ->addColumn('action', fn ($r) => view('components.ui.datatables-actions', [
                'editUrl' => route('akd.pembebanan.edit', $r->encrypted_pembebanan_id),
                'editModal' => true,
                'deleteUrl' => route('akd.pembebanan.destroy', $r->encrypted_pembebanan_id),
            ])->render())
            ->rawColumns(['peran', 'action'])
            ->make(true);
    }

    public function create()
    {
        $kelasKuliahs = $this->kelasService->getAktifOptions();
        $pegawais = $this->pegawaiService->getActiveForSelect();
        $peranOptions = $this->service->getPeranOptions();

        return view('akademik::pages.pembebanan.create-edit-ajax', compact('kelasKuliahs', 'pegawais', 'peranOptions'));
    }

    public function store(PembebananRequest $request)
    {
        $this->service->create($request->validated());

        return jsonSuccess('Pembebanan dosen berhasil ditambahkan.', null, ['reload' => true]);
    }

    public function edit(PembebananDosen $pembebanan)
    {
        $kelasKuliahs = $this->kelasService->getAktifOptions();
        $pegawais = $this->pegawaiService->getActiveForSelect();
        $peranOptions = $this->service->getPeranOptions();

        return view('akademik::pages.pembebanan.create-edit-ajax', compact('pembebanan', 'kelasKuliahs', 'pegawais', 'peranOptions'));
    }

    public function update(PembebananRequest $request, PembebananDosen $pembebanan)
    {
        $this->service->update($pembebanan->pembebanan_id, $request->validated());

        return jsonSuccess('Pembebanan dosen berhasil diperbarui.', null, ['reload' => true]);
    }

    public function destroy(PembebananDosen $pembebanan)
    {
        $this->service->delete($pembebanan->pembebanan_id);

        return jsonSuccess('Pembebanan dosen berhasil dihapus.', null, ['reload' => true]);
    }
}
