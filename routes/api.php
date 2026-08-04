<?php

use Modules\Akademik\Http\Controllers\Api\BiodataApiController;
use Modules\Akademik\Http\Controllers\Api\CekalApiController;
use Modules\Akademik\Http\Controllers\Api\CutiApiController;
use Modules\Akademik\Http\Controllers\Api\MahasiswaApiController;
use Modules\Akademik\Http\Controllers\Api\NilaiApiController;
use Modules\Akademik\Http\Controllers\Api\TransferApiController;
use Modules\Akademik\Http\Controllers\Api\StatusSemesterApiController;
use Illuminate\Support\Facades\Route;

/*
 * Mahasiswa API routes — cross-server ready.
 * These endpoints are consumed by other services (e.g. TracerStudy, PMB, CBT)
 * that need mahasiswa data but run on a separate server.
 *
 * Auth: Sanctum token-based (not session cookies).
 * Controllers: Modules/Mahasiswa/app/Http/Controllers/Api/ (dedicated, JSON-only).
 */
Route::middleware('auth:sanctum')->prefix('v1/mhs')->name('api.mhs.')->group(function () {

    // --- Mahasiswa ---
    Route::get('mahasiswa/search', [MahasiswaApiController::class, 'search'])->name('mahasiswa.search');
    Route::get('mahasiswa', [MahasiswaApiController::class, 'index'])->name('mahasiswa.index');
    Route::get('mahasiswa/{id}', [MahasiswaApiController::class, 'show'])->name('mahasiswa.show');

    // --- Nilai ---
    Route::get('nilai', [NilaiApiController::class, 'index'])->name('nilai.index');
    Route::get('nilai/{id}', [NilaiApiController::class, 'show'])->name('nilai.show');

    // --- Biodata ---
    Route::get('biodata/{mahasiswaId}', [BiodataApiController::class, 'show'])->name('biodata.show');

    // --- Cekal ---
    Route::get('cekal', [CekalApiController::class, 'index'])->name('cekal.index');

    // --- Cuti ---
    Route::get('cuti', [CutiApiController::class, 'index'])->name('cuti.index');

    // --- Transfer ---
    Route::get('transfer', [TransferApiController::class, 'index'])->name('transfer.index');

    // --- Status Semester ---
    Route::get('status-semester', [StatusSemesterApiController::class, 'index'])->name('status-semester.index');
    Route::get('status-semester/{id}', [StatusSemesterApiController::class, 'show'])->name('status-semester.show');
});
