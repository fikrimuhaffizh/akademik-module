<?php

use Illuminate\Support\Facades\Route;
use Modules\Akademik\Http\Controllers\Api\MahasiswaApiController;

/*
 * Mahasiswa API routes — cross-server ready.
 * Konsumsi lintas-modul/lintas-server yang membutuhkan data mahasiswa
 * (mis. TracerStudy, PMB, CBT). Field dikembalikan secara bertahap.
 *
 * Semua endpoint terautentikasi via `auth:sanctum`.
 * Tenant: `service.tenant` membaca header X-Tenant-ID yang dikirim otomatis oleh
 * service_api()/api_http() modul pemanggil (PMB). Tanpa middleware ini
 * CurrentTenant tidak pernah di-set, sehingga global scope BelongsToTenant tidak
 * aktif dan query data mahasiswa bisa menyentuh data tenant lain.
 *
 * Controllers: Modules/Akademik/app/Http/Controllers/Api/ (JSON-only).
 */
Route::middleware(['auth:sanctum', 'service.tenant'])->prefix('v1/mhs')->name('api.mhs.')->group(function () {

    // --- Mahasiswa (identitas dasar: nama, nim, angkatan, prodi, status) ---
    Route::get('mahasiswa', [MahasiswaApiController::class, 'index'])->name('mahasiswa.index');
    Route::get('mahasiswa/search', [MahasiswaApiController::class, 'search'])->name('mahasiswa.search');

    // Tidak ada endpoint khusus "cek NIM": NIM dibaca dari endpoint daftar di
    // atas dengan filter persis `?nim=xxxx`. Satu pintu untuk data mahasiswa,
    // tanpa endpoint bayangan yang harus dijaga sinkron dengan query yang sama.
    // (Pembuatan akd_mahasiswa juga bukan urusan API ini — Akademik menarik
    // kandidat final PMB lewat alur draft, MahasiswaDraftService.)

    // Wildcard {id} WAJIB paling akhir: bila diletakkan sebelum path statis di
    // atas, '/mahasiswa/search' diperlakukan sebagai id → 500 (bukan 404), dan
    // pemanggil lintas modul mati tanpa pesan yang jelas.
    Route::get('mahasiswa/{id}', [MahasiswaApiController::class, 'show'])->name('mahasiswa.show');
});
