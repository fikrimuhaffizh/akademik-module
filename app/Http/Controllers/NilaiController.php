<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Services\NilaiService;
use Modules\Akademik\Services\NilaiImportService;
use Modules\Akademik\Models\Nilai;
use Modules\Akademik\Models\PembebananDosen;
use Illuminate\Http\Request;
use Modules\Akademik\Http\Requests\NilaiStoreRequest;
use Modules\Akademik\Http\Requests\NilaiUpdateRequest;
use Modules\Akademik\Http\Requests\NilaiImportRequest;
use Yajra\DataTables\Facades\DataTables;

class NilaiController extends Controller
{
    public function __construct(
        protected NilaiService $nilaiService,
        protected NilaiImportService $importService,
    ) {
        $this->middleware('permission:akd.nilai.view')->only(['index', 'data', 'khs', 'transkrip']);
        $this->middleware('permission:akd.nilai.create')->only(['store', 'import']);
        $this->middleware('permission:akd.nilai.update')->only(['update']);
        $this->middleware('permission:akd.nilai.delete')->only(['destroy']);
    }

    // ═══════════════════════════════════════════════════════════
    // ADMIN: Import + CRUD nilai
    // ═══════════════════════════════════════════════════════════

    /**
     * Admin: index page — shows import form + nilai table.
     * Mahasiswa: read-only nilai (KHS / transkrip).
     *
     * Disambiguate via ?role=admin or current user role.
     */
    public function index(Request $request)
    {
        $isAdmin = $request->get('role') === 'admin'
            || auth()->user()?->hasAnyRole(['Superadmin', 'Admin', 'PA']);

        if ($isAdmin) {
            $kelasOptions = $this->importService->getKelasOptions();
            return view('akademik::pages.nilai.admin-index', compact('kelasOptions'));
        }

        // Mahasiswa view — redirect to KHS
        return view('akademik::pages.nilai.index');
    }

    public function create()
    {
        return view('akademik::pages.nilai.create-edit-ajax');
    }

    /**
     * DataTables: all nilai (admin) or per-mahasiswa (default).
     */
    public function data(Request $request)
    {
        $query = $this->nilaiService->getFilteredQuery($request->all());

        return Datatables::of($query)
            ->editColumn('mahasiswa_id', fn($row) => $row->mahasiswa?->nama ?? '-')
            ->editColumn('mata_kuliah_id', fn($row) => $row->mataKuliah?->nama ?? '-')
            ->editColumn('nilai_huruf', fn($row) => $row->nilai_huruf ?? '-')
            ->editColumn('is_lulus', fn($row) => $row->is_lulus
                ? '<span class="badge bg-green-lt text-green">Lulus</span>'
                : '<span class="badge bg-red-lt text-red">Belum</span>')
            ->rawColumns(['is_lulus'])
            ->make(true);
    }

    public function store(NilaiStoreRequest $request)
    {
        $this->nilaiService->upsertFinal($request->validated());

        return jsonSuccess('Nilai berhasil disimpan.');
    }

    public function update(NilaiUpdateRequest $request, string $id)
    {
        $nilai = Nilai::findOrFail(decryptIdIfEncrypted($id));
        $this->authorizeDosenKelas($nilai->kelas_id);
        $this->importService->updateNilai($nilai->nilai_akhir_id, $request->validated());

        return jsonSuccess('Nilai berhasil diupdate.');
    }

    /**
     * Guard dosen: pengguna yang terdaftar sebagai pegawai (dosen) hanya boleh
     * mengubah nilai pada kelas yang diampu (tercatat di akd_pembebanan_dosen).
     * Pengguna tanpa profil pegawai (admin/operator) dianggap terotorisasi via
     * permission 'akd.nilai.update'.
     */
    protected function authorizeDosenKelas(?int $kelasId): void
    {
        $pegawai = auth()->user()?->pegawai;

        if (! $pegawai) {
            return;
        }

        $assigned = PembebananDosen::where('kelas_id', $kelasId)
            ->where('pegawai_id', $pegawai->pegawai_id)
            ->exists();

        abort_unless($assigned, 403, 'Anda tidak ditugaskan mengampu kelas ini.');
    }

    public function destroy(string $id)
    {
        $nilai = Nilai::findOrFail(decryptIdIfEncrypted($id));
        $this->importService->deleteNilai($nilai->nilai_akhir_id);

        return jsonSuccess('Nilai berhasil dihapus.');
    }

    /**
     * Import nilai from Excel/CSV.
     */
    public function import(NilaiImportRequest $request)
    {
        $validated = $request->validated();
        $kelasId = decryptIdIfEncrypted($validated['kelas_id']);
        $filePath = $request->file('file')->store('imports/nilai');

        $result = $this->importService->importFromExcel($kelasId, storage_path("app/{$filePath}"));

        return jsonSuccess("Import selesai: {$result['success']} berhasil, {$result['failed']} gagal.", $result);
    }

    /**
     * Download template import.
     */
    public function template()
    {
        $kelasOptions = $this->importService->getKelasOptions();

        // Generate simple CSV template
        $headers = ['NIM', 'Mata Kuliah Kode', 'Nilai Angka'];
        $callback = function () use ($headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template-nilai.csv"',
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // MAHASISWA: KHS + Transkrip (read-only)
    // ═══════════════════════════════════════════════════════════

    public function khs(Request $request)
    {
        $mahasiswaId = $request->get('mahasiswa_id')
            ? decryptIdIfEncrypted($request->get('mahasiswa_id'))
            : $this->resolveMahasiswaId();

        if (! $mahasiswaId) abort(404);

        $periodeId = $request->get('periode_akademik_id');
        $khs = $this->nilaiService->getKhs($mahasiswaId, $periodeId);

        return view('akademik::pages.nilai.khs', compact('khs', 'mahasiswaId', 'periodeId'));
    }

    public function transkrip(Request $request)
    {
        $mahasiswaId = $request->get('mahasiswa_id')
            ? decryptIdIfEncrypted($request->get('mahasiswa_id'))
            : $this->resolveMahasiswaId();

        if (! $mahasiswaId) abort(404);

        $transkrip = $this->nilaiService->getTranskrip($mahasiswaId);

        return view('akademik::pages.nilai.transkrip', compact('transkrip', 'mahasiswaId'));
    }

    protected function resolveMahasiswaId(): ?int
    {
        $user = auth()->user();
        if ($user && $user->hasRole('Mahasiswa')) {
            $mhs = \Modules\Akademik\Models\Mahasiswa::where('user_id', $user->id)->first();
            return $mhs?->mahasiswa_id;
        }
        return null;
    }
}
