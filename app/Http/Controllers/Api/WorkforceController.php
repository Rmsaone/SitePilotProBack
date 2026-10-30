<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Dto\WorkforceDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\WorkforceRequest;
use App\Http\Requests\WorksiteScopedIndexRequest;
use App\Http\Resources\Workforce\IndexWorkforceResource;
use App\Models\Workforce;
use App\Services\WorkforceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WorkforceController extends Controller
{
    public function __construct(
        private readonly WorkforceService $workforceService,
    ) {
    }

    public function index(WorksiteScopedIndexRequest $worksiteScopedIndexRequest): JsonResponse
    {
        return IndexWorkforceResource::collection(
            $this->workforceService->list(
                $worksiteScopedIndexRequest->worksiteId(),
                $worksiteScopedIndexRequest->perPage(15),
            )
        )->response();
    }

    public function store(WorkforceRequest $workforceRequest): JsonResponse
    {
        $validated = $workforceRequest->safe()->toArray();

        /** @var Workforce $workforce */
        $workforce = DB::transaction(
            fn () => $this->workforceService->store(
                WorkforceDto::fromArray($validated)
            )
        );

        return IndexWorkforceResource::make($workforce)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        int $workforce_id,
        WorkforceRequest $workforceRequest
    ): JsonResponse {
        $validated = $workforceRequest->safe()->toArray();

        /** @var Workforce $workforce */
        $workforce = Workforce::query()->findOrFail($workforce_id);

        $updatedWorkforce = DB::transaction(
            fn () => $this->workforceService->update(
                $workforce,
                WorkforceDto::fromArray($validated)
            )
        );

        return IndexWorkforceResource::make($updatedWorkforce)->response();
    }

    public function destroy(int $workforce_id): JsonResponse
    {
        /** @var Workforce $workforce */
        $workforce = Workforce::query()->findOrFail($workforce_id);

        $this->workforceService->destroy($workforce);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
