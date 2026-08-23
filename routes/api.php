<?php

use Modules\Akademik\Http\Controllers\Api\MahasiswaApiController;
use Illuminate\Support\Facades\Route;

/*
 * Mahasiswa API routes — cross-server ready.
 * Konsumsi lintas-modul/lintas-server yang membutuhkan data mahasiswa
 * (mis. TracerStudy, PMB, CBT). Field dikembalikan secara bertahap.
 *
 * Semua endpoint terautentikasi via `auth:sanctum`.
 * Controllers: Modules/Akademik/app/Http/Controllers/Api/ (JSON-only).
 */
Route::middleware('auth:sanctum')->prefix('v1/mhs')->name('api.mhs.')->group(function () {

    // --- Mahasiswa (identitas dasar: nama, nim, angkatan, prodi, status) ---
    Route::get('mahasiswa/search', [MahasiswaApiController::class, 'search'])->name('mahasiswa.search');
    Route::get('mahasiswa', [MahasiswaApiController::class, 'index'])->name('mahasiswa.index');
    Route::get('mahasiswa/{id}', [MahasiswaApiController::class, 'show'])->name('mahasiswa.show');
});
