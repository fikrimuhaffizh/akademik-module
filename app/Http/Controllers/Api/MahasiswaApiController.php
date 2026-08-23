<?php

namespace Modules\Akademik\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Akademik\Models\Mahasiswa;
use Modules\HrCore\Models\StrukturOrganisasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    /**
     * GET /api/v1/mhs/mahasiswa/search?q=...
     *
     * Pencarian ringan untuk autocomplete / select2.
     * Mengembalikan id + text (nama + nim).
     */
    public function search(Request $request): JsonResponse
    {
        $q = $request->input('q', $request->input('search', ''));

        $query = Mahasiswa::query()
            ->select(['mahasiswa_id', 'nim', 'nama'])
            ->where('status', 'aktif');

        if (!empty($q)) {
            $query->where(function ($qBuilder) use ($q) {
                $qBuilder->where('nama', 'like', "%{$q}%")
                    ->orWhere('nim', 'like', "%{$q}%");
            });
        }

        $results = $query->orderBy('nim')->take(20)->get()->map(function ($item) {
            return [
                'id' => encryptId($item->mahasiswa_id),
                'text' => $item->nama . ($item->nim ? ' (' . $item->nim . ')' : ''),
            ];
        });

        return response()->json([
            'results' => $results,
        ]);
    }

    /**
     * GET /api/v1/mhs/mahasiswa
     *
     * Daftar mahasiswa dengan identitas dasar.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Mahasiswa::query()
            ->with('prodi:id,orgunit_id,name')
            ->select([
                'mahasiswa_id',
                'nim',
                'nama',
                'angkatan',
                'prodi_id',
                'status',
            ]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%");
            });
        }

        if ($request->filled('angkatan')) {
            $query->where('angkatan', $request->input('angkatan'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('prodi_id')) {
            $query->where('prodi_id', $request->input('prodi_id'));
        }

        $query->orderBy('nim');

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

        return response()->json($paginator);
    }

    /**
     * GET /api/v1/mhs/mahasiswa/{id}
     *
     * Detail mahasiswa (identitas dasar).
     */
    public function show(string $id): JsonResponse
    {
        $item = Mahasiswa::with('prodi:id,orgunit_id,name')
            ->findOrFail(decryptIdIfEncrypted($id));

        return response()->json([
            'success' => true,
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
