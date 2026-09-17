<?php

namespace Modules\Akademik\Services\References;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * SATU-SATUNYA pintu modul Akademik ke SDM/HRMax (server terpisah).
 *
 * STANDAR lintas modul: satu folder `Services/References` per modul, satu class
 * per modul sumber. Hanya class inilah yang boleh memanggil
 * `service_api('hrmax', ...)` - sebelumnya Akademik men-query
 * `Modules\HrMax\Models\StrukturOrganisasi` langsung, dan itu mati begitu modul
 * HRMax tidak terpasang di server Akademik.
 *
 * Endpoint HRMax yang dipakai:
 *   GET /api/v1/hr-max/prodi            daftar unit organisasi bertipe prodi
 *
 * Kredensial dibaca `IntegrationConfig` => `config('integration.services.hrmax')`
 * (env `HRMAX_SERVICE_URL` / `HRMAX_SERVICE_TOKEN`).
 */
class HrmaxReference
{
    /** Sebab kegagalan panggilan terakhir (null bila sukses). */
    private ?string $lastError = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Ambil seluruh unit organisasi bertipe prodi.
     *
     * @return array<int, array<string, mixed>>|null null bila SDM tidak dapat
     *                                               dihubungi (lihat lastError())
     */
    public function prodis(bool $includeInactive = false): ?array
    {
        $this->lastError = null;

        try {
            $response = service_api('hrmax', 'GET', '/prodi', [
                'include_inactive' => $includeInactive,
            ]);

            if (! is_array($response['data'] ?? null)) {
                throw new RuntimeException('Respons SDM tidak berisi daftar program studi.');
            }

            return array_values($response['data']);
        } catch (\Throwable $e) {
            $this->lastError = $this->describeFailure($e);

            Log::warning('Akademik: gagal mengambil daftar prodi dari SDM.', [
                'error' => $e->getMessage(),
                'sebab' => $this->lastError,
            ]);

            return null;
        }
    }

    /**
     * Nama unit organisasi per `orgunit_id` - SATU panggilan HTTP untuk banyak id
     * (bukan satu panggilan per prodi).
     *
     * @param  array<int, int|string>  $orgunitIds
     * @return array<int, string> peta orgunit_id => name (id tak dikenal dibiarkan kosong)
     */
    public function namaUnit(array $orgunitIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $orgunitIds),
            fn (int $id) => $id > 0,
        )));

        if ($ids === []) {
            return [];
        }

        $rows = $this->prodis(includeInactive: true);

        if ($rows === null) {
            return [];
        }

        $peta = [];

        foreach ($rows as $row) {
            $id = (int) ($row['orgunit_id'] ?? 0);

            if ($id > 0 && in_array($id, $ids, true)) {
                $peta[$id] = (string) ($row['name'] ?? '-');
            }
        }

        return $peta;
    }

    /**
     * Terjemahkan exception integrasi menjadi pesan yang bisa ditindaklanjuti -
     * HTTP 401 bukan berarti .env belum diisi, bisa token yang sudah dicabut.
     */
    private function describeFailure(\Throwable $e): string
    {
        if ($e instanceof RequestException) {
            $status = $e->response?->status();

            return match ($status) {
                401, 403 => "SDM menolak kredensial (HTTP {$status}). Perbarui HRMAX_SERVICE_TOKEN dengan token service yang masih aktif.",
                404 => 'Endpoint prodi SDM tidak ditemukan (HTTP 404). Periksa HRMAX_SERVICE_URL (termasuk prefix /api/v1/hr-max).',
                null => 'SDM membalas error tanpa status: '.$e->getMessage(),
                default => "SDM membalas HTTP {$status}.",
            };
        }

        if ($e instanceof ConnectionException) {
            return 'SDM tidak dapat dihubungi (koneksi ditolak/terlalu lama). Pastikan server SDM aktif dan HRMAX_SERVICE_URL benar.';
        }

        if ($e instanceof RuntimeException) {
            return $e->getMessage();
        }

        return 'SDM tidak dapat dihubungi: '.$e->getMessage();
    }
}
