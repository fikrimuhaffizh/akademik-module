<?php

namespace Modules\Akademik\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Akademik\Models\Mahasiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REST API — Student data.
 *
 * Lightweight endpoint for cross-module consumption.
 * Returns student list with status (aktif/non-aktif/etc.).
 *
 * Auth: Sanctum token (configured at route level).
 */
class MahasiswaApiController extends Controller
{
    /**
     * GET /api/v1/mhs/mahasiswa/search?q=...
     *
     * Lightweight search for autocomplete / select2.
     * Returns minimal fields: id + nama_lengkap + nim.
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
    public function index(Request $request): JsonResponse
    {
        $query = Mahasiswa::query()
            ->select([
                'mahasiswa_id',
                'nim',
                'nama',
                'email',
                'angkatan',
                'status',
                'jenis_masuk',
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

        $query->orderBy('nim');

        $perPage = min((int) $request->input('per_page', 25), 100);
        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($row) {
            return [
                'mahasiswa_id' => encryptId($row->mahasiswa_id),
                'nim' => $row->nim,
                'nama' => $row->nama,
                'email' => $row->email,
                'angkatan' => $row->angkatan,
                'status' => $row->status,
                'jenis_masuk' => $row->jenis_masuk,
                'is_active' => $row->status === 'aktif',
            ];
        });

        return response()->json($paginator);
    }

    /**
     * GET /api/v1/mhs/mahasiswa/{id}
     *
     * Single student detail (includes biodata if available).
     */
    public function show(string $id): JsonResponse
    {
        $item = Mahasiswa::with('biodata')->findOrFail(decryptIdIfEncrypted($id));

        return response()->json([
            'success' => true,
            'data' => [
                'mahasiswa_id' => $item->encrypted_mahasiswa_id,
                'nim' => $item->nim,
                'nama' => $item->nama,
                'email' => $item->email,
                'no_hp' => $item->no_hp,
                'angkatan' => $item->angkatan,
                'status' => $item->status,
                'jenis_masuk' => $item->jenis_masuk,
                'is_active' => $item->status === 'aktif',
                'biodata' => $item->biodata ? [
                    'tempat_lahir' => $item->biodata->tempat_lahir,
                    'tgl_lahir' => $item->biodata->tgl_lahir,
                    'jenis_kelamin' => $item->biodata->jenis_kelamin,
                    'agama' => $item->biodata->agama,
                    'alamat' => $item->biodata->alamat,
                ] : null,
            ],
        ]);
    }
}
