<?php

namespace Modules\Akademik\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Akademik\Services\NilaiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NilaiApiController extends Controller
{
    public function __construct(
        protected NilaiService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->service->getBaseQuery();

        if ($request->filled('mahasiswa_id')) {
            $query->where('mahasiswa_id', $request->input('mahasiswa_id'));
        }

        if ($request->filled('periode_akademik_id')) {
            $query->where('periode_akademik_id', $request->input('periode_akademik_id'));
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        return response()->json($query->paginate($perPage));
    }

    public function show(string $id): JsonResponse
    {
        $item = $this->service->findById($id);

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

}
