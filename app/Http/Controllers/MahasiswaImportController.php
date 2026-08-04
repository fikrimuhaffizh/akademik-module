<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Sys\Http\Requests\ImportRequest;
use Modules\Sys\Http\Requests\ImportStagingReviewRequest;
use Modules\Akademik\Services\MahasiswaImportService;
use Modules\Sys\Services\ImportService;

class MahasiswaImportController extends Controller
{
    public function __construct(
        protected MahasiswaImportService $mahasiswaImportService,
        protected ImportService $importService
    ) {
        $this->middleware('permission:akd.mahasiswa.create');
    }

    public function index()
    {
        $pendingDraft = $this->importService->latestDraft('akmhs', 'mahasiswa');

        return view('sys::pages.import.index', [
            'title' => 'Import Mahasiswa',
            'pretitle' => 'Mahasiswa',
            'subtitle' => 'Upload data mahasiswa dari file CSV.',
            'storeRoute' => route('akd.mahasiswa.import.store'),
            'templateUrl' => asset('templates/template_import_mahasiswa.csv'),
            'description' => 'Gunakan template mahasiswa dengan identitas utama. Kolom <code>email</code> wajib diisi (untuk login user). Jika <code>nim</code> sudah ada di sistem, maka akan terhitung sebagai duplikat / validasi error jika disyaratkan.',
            'pendingDraft' => $pendingDraft,
            'pendingReviewUrl' => $pendingDraft ? route('akd.mahasiswa.import.review', $pendingDraft->batch_id) : null,
            'pendingCancelUrl' => $pendingDraft ? route('akd.mahasiswa.import.cancel', $pendingDraft->batch_id) : null,
            'headers' => ['nim', 'nama', 'email', 'no_hp', 'prodi_kode', 'angkatan', 'jenis_masuk', 'status'],
            'requiredHeaders' => ['nim', 'nama', 'email'],
            'headerInfo' => [
                'nim' => ['example' => '2024001', 'note' => 'Wajib. Nomor Induk Mahasiswa.'],
                'nama' => ['example' => 'Budi Santosa', 'note' => 'Wajib. Nama mahasiswa.'],
                'email' => ['example' => 'budi@kampus.ac.id', 'note' => 'Wajib. Digunakan sebagai akun login.'],
                'no_hp' => ['example' => '081234567890', 'note' => 'Opsional.'],
                'prodi_kode' => ['example' => 'TI', 'note' => 'Opsional. Kode prodi di struktur organisasi.'],
                'angkatan' => ['example' => '2024', 'note' => 'Opsional (default tahun berjalan).'],
                'jenis_masuk' => ['example' => 'reguler', 'note' => 'Opsional. (reguler/pindahan/dll)'],
                'status' => ['example' => 'aktif', 'note' => 'Opsional. (aktif/cuti/keluar/lulus/dropout)'],
            ],
        ]);
    }

    public function store(ImportRequest $request)
    {
        $batchId = $this->mahasiswaImportService->createDraftFromUpload($request->file('file'));

        return jsonSuccess('File berhasil dibaca. Silakan review sebelum import final.', route('akd.mahasiswa.import.review', $batchId));
    }

    public function review(string $batchId)
    {
        return view('sys::pages.import.review', array_merge(
            $this->mahasiswaImportService->reviewData($batchId),
            [
                'title' => 'Review Import Mahasiswa',
                'pretitle' => 'Import Mahasiswa',
                'subtitle' => 'Periksa data mahasiswa sebelum masuk ke data utama.',
                'backUrl' => route('akd.mahasiswa.index'),
                'reuploadRoute' => route('akd.mahasiswa.import.reupload', $batchId),
                'updateRoute' => route('akd.mahasiswa.import.update', $batchId),
                'commitRoute' => route('akd.mahasiswa.import.commit', $batchId),
                'cancelRoute' => route('akd.mahasiswa.import.cancel', $batchId),
                'textareaColumns' => [],
            ]
        ));
    }

    public function reupload(ImportRequest $request, string $batchId)
    {
        $this->mahasiswaImportService->reuploadDraftFromUpload($request->file('file'), $batchId);

        return jsonSuccess('File berhasil diupload ulang. Data review lama sudah diganti.', route('akd.mahasiswa.import.review', $batchId));
    }

    public function update(ImportStagingReviewRequest $request, string $batchId)
    {
        $this->mahasiswaImportService->updateDraft($batchId, $request->validated()['rows']);

        return jsonSuccess('Review import berhasil diperbarui.', route('akd.mahasiswa.import.review', $batchId));
    }

    public function commit(string $batchId)
    {
        $results = $this->mahasiswaImportService->commit($batchId);

        return jsonSuccess("Import mahasiswa selesai. {$results['success']} berhasil diimport.", route('akd.mahasiswa.index'));
    }

    public function cancel(string $batchId)
    {
        $batch = $this->importService->findBatch($batchId, 'akmhs', 'mahasiswa');
        $this->importService->cancelDraft($batch);

        return jsonSuccess('Proses review import berhasil dibatalkan.', route('akd.mahasiswa.index'));
    }

    public function template()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_import_mahasiswa.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['nim', 'nama', 'email', 'no_hp', 'prodi_kode', 'angkatan', 'jenis_masuk', 'status']);
            fputcsv($file, ['2024001', 'Budi Santoso', 'budi@email.com', '081234567890', 'TI', '2024', 'reguler', 'aktif']);
            fputcsv($file, ['2024002', 'Siti Aminah', 'siti@email.com', '081234567891', 'SI', '2024', 'reguler', 'aktif']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

