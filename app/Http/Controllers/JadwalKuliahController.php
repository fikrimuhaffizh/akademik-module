<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Akademik\Http\Requests\JadwalKuliahRequest;
use Modules\Akademik\Models\JadwalKuliah;
use Modules\Akademik\Services\JadwalKuliahService;
use Modules\Akademik\Services\KelasKuliahService;
use Modules\Akademik\Services\RuangKuliahService;
use Yajra\DataTables\DataTables;

class JadwalKuliahController extends Controller
{
    public function __construct(
        protected JadwalKuliahService $service,
        protected RuangKuliahService $ruangService,
        protected KelasKuliahService $kelasService,
    ) {
        $this->middleware('permission:akd.jadwal-akd.view')->only(['index', 'data']);
        $this->middleware('permission:akd.jadwal-akd.create')->only(['create', 'store']);
        $this->middleware('permission:akd.jadwal-akd.update')->only(['edit', 'update']);
        $this->middleware('permission:akd.jadwal-akd.delete')->only(['destroy']);
    }

    public function index()
    {
        return view('akademik::pages.jadwal-akd.index', [
            'total' => $this->service->getBaseQuery()->count(),
        ]);
    }

    public function data(Request $request)
    {
        $query = $this->service->getFilteredQuery($request->all());
        $overlapMap = $this->service->buildOverlapMap(clone $query);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('kelas', fn ($r) => $r->kelas?->nama_kelas ?? '-')
            ->addColumn('ruang', fn ($r) => $r->ruang ? $r->ruang->nama : ($r->isOnline() ? '<span class="badge bg-info-lt text-info">Online</span>' : '-'))
            ->editColumn('hari', fn ($r) => ucfirst($r->hari))
            ->addColumn('waktu', fn ($r) => $r->jam_mulai.' - '.$r->jam_selesai)
            ->addColumn('status', function ($r) use ($overlapMap) {
                if (! empty($overlapMap[$r->jadwal_id])) {
                    return '<span class="status status-danger">Overlap (Konflik)</span>';
                }

                return '<span class="status status-success">Aman</span>';
            })
            ->addColumn('action', fn ($r) => view('components.ui.datatables-actions', [
                'editUrl' => route('akd.jadwal-akd.edit', $r->encrypted_jadwal_id),
                'editModal' => true,
                'deleteUrl' => route('akd.jadwal-akd.destroy', $r->encrypted_jadwal_id),
            ])->render())
            ->rawColumns(['ruang', 'status', 'action'])
            ->make(true);
    }

    public function create()
    {
        $kelasKuliahs = $this->kelasService->getAktifOptions();
        $ruangKuliahs = $this->ruangService->getAll();
        $hariOptions = $this->service->getHariOptions();

        return view('akademik::pages.jadwal-akd.create-edit-ajax', compact('kelasKuliahs', 'ruangKuliahs', 'hariOptions'));
    }

    public function store(JadwalKuliahRequest $request)
    {
        $this->service->create($request->validated());

        return jsonSuccess('Jadwal kuliah berhasil ditambahkan.', null, ['reload' => true]);
    }

    public function edit(JadwalKuliah $jadwal_kuliah)
    {
        $kelasKuliahs = $this->kelasService->getAktifOptions();
        $ruangKuliahs = $this->ruangService->getAll();
        $hariOptions = $this->service->getHariOptions();

        return view('akademik::pages.jadwal-akd.create-edit-ajax', compact('jadwal_kuliah', 'kelasKuliahs', 'ruangKuliahs', 'hariOptions'));
    }

    public function update(JadwalKuliahRequest $request, JadwalKuliah $jadwal_kuliah)
    {
        $this->service->update($jadwal_kuliah->jadwal_id, $request->validated());

        return jsonSuccess('Jadwal kuliah berhasil diperbarui.', null, ['reload' => true]);
    }

    public function destroy(JadwalKuliah $jadwal_kuliah)
    {
        $this->service->delete($jadwal_kuliah->jadwal_id);

        return jsonSuccess('Jadwal kuliah berhasil dihapus.', null, ['reload' => true]);
    }
}
