<?php

namespace Modules\Akademik\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Akademik\Services\CutiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CutiApiController extends Controller
{
    public function __construct(
        protected CutiService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->service->getBaseQuery();

        if ($request->filled('mahasiswa_id')) {
            $query->where('mahasiswa_id', $request->input('mahasiswa_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        return response()->json($query->paginate($perPage));
    }
}
