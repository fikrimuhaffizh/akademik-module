<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Http\Requests\MahasiswaRequest;
use Modules\Akademik\Services\MahasiswaService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Modules\Akademik\Exports\MahasiswaExport;
use Maatwebsite\Excel\Facades\Excel;
use Modules\HrCore\Services\StrukturOrganisasiService;

class MahasiswaController extends Controller
{
    public function __construct(protected MahasiswaService $service, protected StrukturOrganisasiService $strukturService)
    {
        // export & searchSelect2 ikut di-gate view —
        // sebelumnya terbuka sehingga seluruh PII mahasiswa bisa diunduh/
        // di-enumerasi oleh semua user login.
        $this->middleware('permission:akd.mahasiswa.view')->only(['index', 'data', 'export', 'searchSelect2']);
        $this->middleware('permission:akd.mahasiswa.create')->only(['create', 'store']);
        $this->middleware('permission:akd.mahasiswa.update')->only(['edit', 'update']);
        $this->middleware('permission:akd.mahasiswa.delete')->only(['destroy']);
    }

    public function index()
    {
        $angkatans = $this->service->getAngkatans();
        $prodis = $this->strukturService->getAllProdi();

        return view('akademik::pages.mahasiswa.index', compact('angkatans', 'prodis'));
    }

    public function data(Request $request)
    {
        $query = $this->service->getFilteredQuery($request->only(['search', 'status', 'angkatan', 'prodi_id']));
        $query->with(['prodi', 'riwayatStatus' => function ($q) {
            $q->latest('tgl_efektif')->limit(1);
        }]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('mahasiswa_info', function ($row) {
                $nama = e($row->nama ?? '-');
                $nim = e($row->nim ?? '-');
                $prodi = e($row->prodi?->name ?? '-');
                $angkatan = e($row->angkatan ?? '-');
                $semester = e($row->semester_masuk ?? '-');
                $jenisMasuk = e(ucfirst($row->jenis_masuk ?? '-'));
                return "<div><strong>{$nama}</strong> <span class=\"text-secondary ms-1\">({$nim})</span></div>"
                    . "<div class=\"text-secondary small mt-1\">{$prodi} • Angkatan {$angkatan} • Semester Masuk {$semester} • {$jenisMasuk}</div>";
            })
            ->addColumn('status_badge', function ($row) {
                $badge = status_badge($row->status);
                $riwayat = $row->riwayatStatus;
                if ($riwayat) {
                    $tgl = formatTanggalIndo($riwayat->tgl_efektif);
                    $badge .= "<div class=\"text-secondary small mt-1\">{$riwayat->status_lama} → {$riwayat->status_baru} ({$tgl})</div>";
                }
                return $badge;
            })
            ->addColumn('kurikulum', function ($row) {
                return e($row->kurikulum_kode ?? '-');
            })
            ->addColumn('action', fn ($row) => view('components.ui.datatables-actions', [
                'viewUrl' => route('akd.mahasiswa.detail', encryptId($row->mahasiswa_id)),
            ])->render())
            ->rawColumns(['mahasiswa_info', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * Endpoint select2: cari mahasiswa by NIM/nama (untuk pilih KRS di sisi admin/PA).
     */
    public function searchSelect2(Request $request)
    {
        $term = trim((string) $request->input('search', ''));
        $rows = $this->service->searchSelect2($term);

        return response()->json(['results' => $rows]);
    }

    public function create()
    {
        return view('akademik::pages.mahasiswa.create-edit-ajax');
    }

    public function store(MahasiswaRequest $request)
    {
        $this->service->create($request->validated());
        return jsonSuccess('Mahasiswa berhasil ditambahkan.', null, ['reload' => true]);
    }

    public function edit(string $id)
    {
        $row = $this->service->findById($id);
        return view('akademik::pages.mahasiswa.create-edit-ajax', compact('row'));
    }

    public function update(MahasiswaRequest $request, string $id)
    {
        $this->service->update($id, $request->validated());
        return jsonSuccess('Mahasiswa berhasil diperbarui.', null, ['reload' => true]);
    }

    public function export(Request $request)
    {
        $filters = $request->only(['search', 'status', 'angkatan', 'prodi_id']);
        $filename = 'mahasiswa-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new MahasiswaExport($filters), $filename);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);
        return jsonSuccess('Mahasiswa berhasil dihapus.', null, ['reload' => true]);
    }
}
