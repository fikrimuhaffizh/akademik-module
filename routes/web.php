<?php

use Modules\Akademik\Http\Controllers\BiodataController;
use Modules\Akademik\Http\Controllers\CekalController;
use Modules\Akademik\Http\Controllers\CutiController;
use Modules\Akademik\Http\Controllers\DashboardController;
use Modules\Akademik\Http\Controllers\MahasiswaDashboardController;
use Modules\Akademik\Http\Controllers\EdomController;
use Modules\Akademik\Http\Controllers\EdomMahasiswaController;
use Modules\Akademik\Http\Controllers\JadwalKuliahController;
use Modules\Akademik\Http\Controllers\KalenderAkademikController;
use Modules\Akademik\Http\Controllers\KelasKuliahController;
use Modules\Akademik\Http\Controllers\KrsController;
use Modules\Akademik\Http\Controllers\MahasiswaController;
use Modules\Akademik\Http\Controllers\MahasiswaDetailController;
use Modules\Akademik\Http\Controllers\MahasiswaImportController;
use Modules\Akademik\Http\Controllers\NilaiController;
use Modules\Akademik\Http\Controllers\PembebananController;
use Modules\Akademik\Http\Controllers\PembimbingMahasiswaController;
use Modules\Akademik\Http\Controllers\PembimbingMahasiswaImportController;
use Modules\Akademik\Http\Controllers\PenawaranController;
use Modules\Akademik\Http\Controllers\PeriodeAkademikController;
use Modules\Akademik\Http\Controllers\RiwayatStatusController;
use Modules\Akademik\Http\Controllers\RuangKuliahController;
use Modules\Akademik\Http\Controllers\TahunAjaranController;
use Modules\Akademik\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'check.expired', 'module:akademik'])->prefix('akd')->name('akd.')->group(function () {

    // --- Dashboard ---
    Route::redirect('/', '/akd/dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/mahasiswa', [MahasiswaDashboardController::class, 'index'])->name('dashboard.mahasiswa');

    // ═══════════════════════════════════════════════════════════
    // AKADEMIK (from Perkuliahan)
    // ═══════════════════════════════════════════════════════════

    Route::get('/tahun-ajaran/data', [TahunAjaranController::class, 'data'])->name('tahun-ajaran.data');
    Route::resource('tahun-ajaran', TahunAjaranController::class);

    Route::get('/periode-akademik/data', [PeriodeAkademikController::class, 'data'])->name('periode-akademik.data');
    Route::resource('periode-akademik', PeriodeAkademikController::class);

    Route::get('/ruang-kuliah/data', [RuangKuliahController::class, 'data'])->name('ruang-kuliah.data');
    Route::resource('ruang-kuliah', RuangKuliahController::class);

    Route::get('/kalender-akademik/data', [KalenderAkademikController::class, 'data'])->name('kalender-akademik.data');
    Route::resource('kalender-akademik', KalenderAkademikController::class);

    Route::get('/penawaran/data', [PenawaranController::class, 'data'])->name('penawaran.data');
    Route::get('penawaran/generate/form', [PenawaranController::class, 'generateForm'])->name('penawaran.generate.form');
    Route::post('penawaran/generate', [PenawaranController::class, 'generateFromKurikulum'])->name('penawaran.generate');
    Route::resource('penawaran', PenawaranController::class);

    Route::get('/kelas-kuliah/data', [KelasKuliahController::class, 'data'])->name('kelas-kuliah.data');
    Route::resource('kelas-kuliah', KelasKuliahController::class);

    Route::get('/jadwal-kuliah/data', [JadwalKuliahController::class, 'data'])->name('jadwal-kuliah.data');
    Route::resource('jadwal-kuliah', JadwalKuliahController::class);

    Route::get('/pembebanan/data', [PembebananController::class, 'data'])->name('pembebanan.data');
    Route::resource('pembebanan', PembebananController::class);

    Route::get('/pembimbing-mahasiswa/data', [PembimbingMahasiswaController::class, 'data'])->name('pembimbing-mahasiswa.data');
    Route::resource('pembimbing-mahasiswa', PembimbingMahasiswaController::class);
    Route::get('pembimbing-mahasiswa/import', [PembimbingMahasiswaImportController::class, 'index'])->name('pembimbing-mahasiswa.import.index');
    Route::post('pembimbing-mahasiswa/import', [PembimbingMahasiswaImportController::class, 'store'])->name('pembimbing-mahasiswa.import.store');
    Route::post('pembimbing-mahasiswa/import/{batchId}/review', [PembimbingMahasiswaImportController::class, 'review'])->name('pembimbing-mahasiswa.import.review');
    Route::delete('pembimbing-mahasiswa/import/{batchId}', [PembimbingMahasiswaImportController::class, 'cancel'])->name('pembimbing-mahasiswa.import.cancel');


    // --- KRS Admin ---
    Route::get('/krs/data', [KrsController::class, 'data'])->name('krs.data');
    Route::resource('krs', KrsController::class)->except(['show'])->parameters(['krs' => 'krs']);

    // --- Nilai (Import) ---
    Route::get('nilai/create', [NilaiController::class, 'create'])->name('nilai.create');
    Route::get('nilai', [NilaiController::class, 'index'])->name('nilai.index');
    Route::get('nilai/data', [NilaiController::class, 'data'])->name('nilai.data');
    Route::post('nilai', [NilaiController::class, 'store'])->name('nilai.store');
    Route::put('nilai/{id}', [NilaiController::class, 'update'])->name('nilai.update');
    Route::delete('nilai/{id}', [NilaiController::class, 'destroy'])->name('nilai.destroy');
    Route::post('nilai/import', [NilaiController::class, 'import'])->name('nilai.import');
    Route::get('nilai/template', [NilaiController::class, 'template'])->name('nilai.template');

    // --- EDOM Admin ---
    Route::get('/edom', [EdomController::class, 'adminIndex'])->name('edom.index');
    Route::get('/edom/data', [EdomController::class, 'adminData'])->name('edom.data');
    Route::post('/edom/{edomKelas}/activate', [EdomController::class, 'activate'])->name('edom.activate');
    Route::post('/edom/{edomKelas}/close', [EdomController::class, 'close'])->name('edom.close');
    Route::get('/edom/{edomKelas}/rekap', [EdomController::class, 'rekap'])->name('edom.rekap');
    Route::post('/edom/generate/{periodeAkademik}', [EdomController::class, 'generate'])->name('edom.generate');

    // --- EDOM Mahasiswa ---
    Route::get('/edom/saya', [EdomController::class, 'mahasiswaIndex'])->name('edom.mahasiswa');
    Route::get('/edom/{edomKelas}/isi', [EdomController::class, 'mulaiIsi'])->name('edom.mulai');

    // ═══════════════════════════════════════════════════════════
    // MAHASISWA (from Modules\Mahasiswa)
    // ═══════════════════════════════════════════════════════════

    // Data endpoints (MUST be before resource routes)
    Route::get('mahasiswa/data', [MahasiswaController::class, 'data'])->name('mahasiswa.data');
    Route::get('mahasiswa/search', [MahasiswaController::class, 'searchSelect2'])->name('mahasiswa.search');
    Route::get('biodata/data', [BiodataController::class, 'data'])->name('biodata.data');
    Route::get('riwayat-status/data', [RiwayatStatusController::class, 'data'])->name('riwayat-status.data');
    Route::get('cekal/data', [CekalController::class, 'data'])->name('cekal.data');
    Route::get('cuti/data', [CutiController::class, 'data'])->name('cuti.data');
    Route::get('transfer/data', [TransferController::class, 'data'])->name('transfer.data');

    // Export Mahasiswa
    Route::get('mahasiswa/export', [MahasiswaController::class, 'export'])->name('mahasiswa.export');

    // Mahasiswa CRUD (show excluded — handled by detail route)
    Route::resource('mahasiswa', MahasiswaController::class)->except(['show']);

    // Mahasiswa Detail (tab-based — MUST be after resource)
    Route::get('mahasiswa/{id}/{tab?}', [MahasiswaDetailController::class, 'show'])
        ->name('mahasiswa.detail')
        ->middleware('permission:akd.mahasiswa.view');

    // Biodata
    Route::resource('biodata', BiodataController::class)->except(['show']);

    // Riwayat Status
    Route::resource('riwayat-status', RiwayatStatusController::class)->except(['show']);

    // Nilai Mahasiswa (read-only)
    Route::get('nilai-mahasiswa', [\Modules\Akademik\Http\Controllers\NilaiController::class, 'index'])->name('nilai-mahasiswa.index');

    // Cekal
    Route::resource('cekal', CekalController::class)->except(['show']);

    // Cuti
    Route::resource('cuti', CutiController::class)->except(['show']);

    // Transfer
    Route::resource('transfer', TransferController::class)->except(['show']);
    Route::post('transfer/{id}/approve', [TransferController::class, 'approve'])->name('transfer.approve');
    Route::post('transfer/{id}/reject', [TransferController::class, 'reject'])->name('transfer.reject');

    // KRS Mahasiswa (sisi mahasiswa)
    Route::get('krs-mahasiswa', [\Modules\Akademik\Http\Controllers\KrsController::class, 'mahasiswaIndex'])->name('krs-mahasiswa.index');
    Route::post('krs/pilih', [\Modules\Akademik\Http\Controllers\KrsController::class, 'pilih'])->name('krs.pilih');
    Route::get('krs/form/{mahasiswaId}', [\Modules\Akademik\Http\Controllers\KrsController::class, 'form'])->name('krs.form');
    Route::get('krs/datatable', [\Modules\Akademik\Http\Controllers\KrsController::class, 'datatable'])->name('krs.datatable');
    Route::post('krs/toggle', [\Modules\Akademik\Http\Controllers\KrsController::class, 'toggle'])->name('krs.toggle');
    Route::post('krs/ajukan', [\Modules\Akademik\Http\Controllers\KrsController::class, 'ajukan'])->name('krs.ajukan');

    // EDOM Mahasiswa (sisi mahasiswa)
    Route::get('edom-mahasiswa', [EdomMahasiswaController::class, 'index'])->name('edom-mahasiswa.index');

    // Import Mahasiswa
    Route::prefix('import-mahasiswa')->name('mahasiswa.import.')->group(function () {
        Route::get('/', [MahasiswaImportController::class, 'index'])->name('index');
        Route::post('/', [MahasiswaImportController::class, 'store'])->name('store');
        Route::get('{batchId}/review', [MahasiswaImportController::class, 'review'])->name('review');
        Route::post('{batchId}/reupload', [MahasiswaImportController::class, 'reupload'])->name('reupload');
        Route::put('{batchId}', [MahasiswaImportController::class, 'update'])->name('update');
        Route::post('{batchId}/commit', [MahasiswaImportController::class, 'commit'])->name('commit');
        Route::post('{batchId}/cancel', [MahasiswaImportController::class, 'cancel'])->name('cancel');
    });
});
