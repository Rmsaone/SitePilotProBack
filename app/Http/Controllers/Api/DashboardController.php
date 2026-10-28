<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardRequest;
use App\Http\Resources\Dashboard\DashboardResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {
    }

    public function index(DashboardRequest $dashboardRequest): JsonResponse
    {
        return DashboardResource::make(
            $this->dashboardService->summarize($dashboardRequest->period())
        )->response();
    }
}
