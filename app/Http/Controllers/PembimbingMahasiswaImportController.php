<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Sys\Http\Requests\ImportRequest;
use Modules\Sys\Http\Requests\ImportStagingReviewRequest;
use Modules\Akademik\Services\PembimbingMahasiswaImportService;
use Modules\Sys\Services\ImportService;

class PembimbingMahasiswaImportController extends Controller
{
    public function __construct(
        protected PembimbingMahasiswaImportService $pembimbingImportService,
        protected ImportService $importService
    ) {
        $this->middleware('permission:akd.pembimbing-mahasiswa.create');
    }

    public function index()
    {
        $pendingDraft = $this->importService->latestDraft('akad', 'pembimbing-mahasiswa');

        return view('sys::pages.import.index', [
            'title' => 'Import Pembimbing Mahasiswa',
            'pretitle' => 'Pembimbing Mahasiswa',
            'subtitle' => 'Upload data pembimbing mahasiswa dari file CSV.',
            'storeRoute' => route('akd.pembimbing-mahasiswa.import.store'),
            'templateUrl' => asset('templates/template_import_pembimbing_mahasiswa.csv'),
            'description' => 'Gunakan template ini. Kolom <code>nim</code> dan <code>nidn</code> wajib diisi.',
            'pendingDraft' => $pendingDraft,
            'pendingReviewUrl' => $pendingDraft ? route('akd.pembimbing-mahasiswa.import.review', $pendingDraft->batch_id) : null,
            'pendingCancelUrl' => $pendingDraft ? route('akd.pembimbing-mahasiswa.import.cancel', $pendingDraft->batch_id) : null,
            'headers' => ['nim', 'nidn', 'jenis_pembimbing'],
            'requiredHeaders' => ['nim', 'nidn'],
            'headerInfo' => [
                'nim'              => ['example' => '2024001', 'note' => 'Wajib. NIM mahasiswa.'],
                'nidn'             => ['example' => '123456789', 'note' => 'Wajib. NIDN dosen pembimbing.'],
                'jenis_pembimbing' => ['example' => 'akademik', 'note' => 'Opsional. Default: akademik.'],
            ],
        ]);
    }

    public function store(ImportRequest $request)
    {
        return $this->importService->processUpload(
            'akad', 'pembimbing-mahasiswa', $request,
            $this->pembimbingImportService,
            ['nim', 'nidn', 'jenis_pembimbing']
        );
    }

    public function review(string $batchId, ImportStagingReviewRequest $request)
    {
        return $this->importService->processReview(
            $batchId, $request,
            $this->pembimbingImportService
        );
    }

    public function cancel(string $batchId)
    {
        return $this->importService->cancelDraft($batchId);
    }
}
