<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Akademik Service (K4 — Pisah Server)
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk komunikasi dengan modul Akademik via HTTP API.
    | PMB memanggil Akademik untuk: create mahasiswa, cek NIM.
    |
    | base_url → URL dasar API Akademik (termasuk /api).
    |             Wajib diisi. Jika kosong, throw RuntimeException.
    | token    → Sanctum personal access token untuk service-to-service auth.
    | signing_key → HMAC-SHA256 secret untuk webhook signature verification.
    |
    | Env vars:
    |   AKADEMIK_SERVICE_URL, AKADEMIK_SERVICE_TOKEN, AKADEMIK_SIGNING_KEY
    |
    */
    'base_url' => env('AKADEMIK_SERVICE_URL', ''),
    'token' => env('AKADEMIK_SERVICE_TOKEN', ''),
    'signing_key' => env('AKADEMIK_SIGNING_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Kredensial ke PMB tidak di sini
    |--------------------------------------------------------------------------
    |
    | Kredensial untuk memanggil PMB (PMB_SERVICE_URL / PMB_SERVICE_TOKEN)
    | dibaca service_api() dari config/integration.php milik server ini — lihat
    | Akademik\Services\References\PmbReference. Tidak ada salinan di config
    | modul supaya tidak ada dua sumber kebenaran yang bisa berbeda.
    |
    */
];
