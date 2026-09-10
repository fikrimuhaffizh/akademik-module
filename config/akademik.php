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
    | PMB API (Akademik calling back PMB)
    |--------------------------------------------------------------------------
    |
    | Akademik memanggil PMB untuk: finalize pendaftaran (nim_final).
    |
    */
    'pmb_base_url' => env('PMB_SERVICE_URL', ''),
    'pmb_token' => env('PMB_SERVICE_TOKEN', ''),
];
