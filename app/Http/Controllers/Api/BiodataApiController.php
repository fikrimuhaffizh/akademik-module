<?php

namespace Modules\Akademik\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Akademik\Services\BiodataService;
use Illuminate\Http\JsonResponse;

class BiodataApiController extends Controller
{
    public function __construct(
        protected BiodataService $service,
    ) {}

    public function show(string $mahasiswaId): JsonResponse
    {
        $item = $this->service->findByMahasiswa((int) decryptIdIfEncrypted($mahasiswaId));

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }
}
