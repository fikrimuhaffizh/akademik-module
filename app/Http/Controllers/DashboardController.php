<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Akademik\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $service)
    {
    }

    public function index()
    {
        return view('akademik::pages.dashboard.admin', [
            'stats' => $this->service->getAdminStats(),
        ]);
    }
}
