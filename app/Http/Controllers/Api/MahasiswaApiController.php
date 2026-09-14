<?php

namespace Modules\Akademik\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Akademik\Services\MahasiswaService;

/**
 * REST API — Student data.
 *
 * Endpoint ringan untuk konsumsi lintas-modul / lintas-server.
 * Mengembalikan identitas dasar mahasiswa: nama, nim, angkatan,
 * prodi, dan status. Field lain dapat ditambah di kemudian hari.
 *
 * Auth: Sanctum token (diatur di level route).
 * Controller: Modules/Akademik/app/Http/Controllers/Api/ (JSON-only).
 */
class MahasiswaApiController extends Controller
{
    public function __construct(protected MahasiswaService $mahasiswaService) {}

    /**
     * GET /api/v1/mhs/mahasiswa/search?q=...
     *
     * Pencarian ringan untuk autocomplete / select2.
     * Mengembalikan id + text (nama + nim).
     */
    public function search(Request $request): JsonResponse
    {
        $q = $request->input('q', $request->input('search', ''));

        $results = $this->mahasiswaService->getApiSearch($q)->map(function ($item) {
            return [
                'id' => encryptId($item->mahasiswa_id),
                'text' => $item->nama . ($item->nim ? ' (' . $item->nim . ')' : ''),
            ];
        });

        return jsonSuccess('Hasil pencarian mahasiswa.', null, [
            'results' => $results,
        ]);
    }

    /**
     * GET /api/v1/mhs/mahasiswa
     *
     * Daftar mahasiswa dengan identitas dasar.
     *
     * Filter: `nim` (persis), `angkatan`, `status`, `prodi_id`, `search`
     * (nama/NIM mengandung), `per_page`.
     *
     * Cek satu NIM tidak perlu endpoint sendiri: panggil `?nim=25060001` dan
     * lihat apakah `data` kosong.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->mahasiswaService->getApiIndexQuery($request->all());

        $perPage = min((int) $request->input('per_page', 25), 100);
        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($row) {
            return [
                'mahasiswa_id' => encryptId($row->mahasiswa_id),
                'nim' => $row->nim,
                'nama' => $row->nama,
                'angkatan' => $row->angkatan,
                'prodi' => $row->prodi ? $row->prodi->name : null,
                'status' => $row->status,
            ];
        });

        return jsonPaginated($paginator, 'Daftar mahasiswa berhasil diambil.');
    }

    /**
     * GET /api/v1/mhs/mahasiswa/{id}
     *
     * Detail mahasiswa (identitas dasar).
     */
    public function show(string $id): JsonResponse
    {
        $item = $this->mahasiswaService->findApi(decryptIdIfEncrypted($id));

        return jsonSuccess('Detail mahasiswa berhasil diambil.', null, [
            'data' => [
                'mahasiswa_id' => $item->encrypted_mahasiswa_id,
                'nim' => $item->nim,
                'nama' => $item->nama,
                'angkatan' => $item->angkatan,
                'prodi' => $item->prodi ? $item->prodi->name : null,
                'status' => $item->status,
            ],
        ]);
    }
}
