<?php

namespace Modules\Akademik\Services\References;

use Illuminate\Support\Facades\Log;

/**
 * Referensi & perintah milik modul PMB yang dipakai modul Akademik.
 *
 * STANDAR lintas modul: satu folder `Services/References` per modul, satu class
 * per modul sumber. Hanya class di folder ini yang boleh memanggil
 * `service_api('pmb', ...)`. Service domain (mis. MahasiswaDraftService),
 * controller, request, dan model tidak boleh memanggil modul lain langsung —
 * dan TIDAK boleh menyentuh tabel/view milik PMB (mis. `v_pmb_kandidat_final`),
 * karena Akademik & PMB berdiri di server masing-masing.
 *
 * Endpoint PMB yang dipakai:
 *   GET  /api/v1/pmb/kandidat/final            (kandidat siap sync: Diterima + NIM + DU lunas)
 *   POST /api/v1/pmb/pendaftaran/{id}/finalize (catat nim_final + finalized_at)
 *
 * Kredensial dibaca lewat `service_api()` dari `config/integration.php` server
 * ini (PMB_SERVICE_URL / PMB_SERVICE_TOKEN), jadi modul PMB tidak perlu
 * terinstal di server Akademik.
 *
 * Semua method "aman gagal": kegagalan dicatat di log dan ditandai lewat nilai
 * balik (null/false) supaya pemanggil bisa memberi pesan yang jelas — tidak ada
 * exception yang bocor ke pengguna.
 */
class PmbReference
{
    /** Timeout khusus tarik kandidat (payload besar). */
    private const LIST_TIMEOUT = 60;

    /**
     * Daftar kandidat final dari PMB.
     *
     * @return array<int, array<string, mixed>>|null null = PMB tidak dapat dihubungi
     *                                               atau integrasi belum dikonfigurasi.
     *                                               Array kosong = PMB menjawab "tidak ada data".
     */
    public function kandidatFinal(?int $periodeId = null): ?array
    {
        try {
            $response = service_api('pmb', 'GET', '/kandidat/final', array_filter([
                'periode_id' => $periodeId,
            ]), ['timeout' => self::LIST_TIMEOUT]);
        } catch (\Throwable $e) {
            Log::warning('Akademik: gagal mengambil kandidat final dari PMB.', [
                'periode_id' => $periodeId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        $rows = $response['data'] ?? [];

        return is_array($rows)
            ? array_values(array_filter($rows, 'is_array'))
            : [];
    }

    /**
     * Kabari PMB bahwa pendaftaran sudah menjadi mahasiswa.
     *
     * Dipanggil setelah draft di-submit; kegagalan di sini tidak membatalkan
     * mahasiswa yang sudah dibuat, hanya dicatat di log.
     *
     * @return bool true bila PMB menerima dan mencatat nim_final
     */
    public function finalize(int $pendaftaranId, string $nimFinal, ?string $finalizedAt = null): bool
    {
        if ($pendaftaranId <= 0) {
            return false;
        }

        try {
            service_api('pmb', 'POST', "/pendaftaran/{$pendaftaranId}/finalize", [
                'nim_final' => $nimFinal,
                'finalized_at' => $finalizedAt,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Akademik: gagal mengabari PMB (finalize) — draft tetap submitted.', [
                'pendaftaran_id' => $pendaftaranId,
                'nim_final' => $nimFinal,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
