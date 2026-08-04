<?php

namespace Modules\Akademik\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Akademik\Services\CekalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CekalApiController extends Controller
{
    public function __construct(
        protected CekalService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->service->getBaseQuery();

        if ($request->filled('mahasiswa_id')) {
            $query->where('mahasiswa_id', $request->input('mahasiswa_id'));
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->input('jenis'));
        }

        if ($request->filled('is_aktif')) {
            $query->where('is_aktif', $request->boolean('is_aktif'));
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        return response()->json($query->paginate($perPage));
    }
}
