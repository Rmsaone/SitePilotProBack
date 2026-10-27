<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Dto\WorksiteDto;
use App\Dto\WorksiteFilterDto;
use App\Enums\WorksiteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWorksiteStatusRequest;
use App\Http\Requests\WorksiteIndexRequest;
use App\Http\Requests\WorksiteRequest;
use App\Http\Resources\Worksite\IndexWorksiteResource;
use App\Http\Resources\Worksite\ShowWorksiteResource;
use App\Http\Resources\Worksite\WorksiteSummaryResource;
use App\Models\Worksite;
use App\Services\WorksiteService;
use App\Services\WorksiteSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WorksiteController extends Controller
{
    private const array SHOW_RELATIONS = ['client', 'users', 'quotes'];

    public function __construct(
        private readonly WorksiteService $worksiteService,
        private readonly WorksiteSummaryService $worksiteSummaryService,
    ) {
    }

    public function index(WorksiteIndexRequest $worksiteIndexRequest): JsonResponse
    {
        return IndexWorksiteResource::collection(
            $this->worksiteService->list(
                WorksiteFilterDto::fromArray($worksiteIndexRequest->safe()->toArray())
            )
        )->response();
    }

    public function show(int $worksite_id): JsonResponse
    {
        /** @var Worksite $worksite */
        $worksite = Worksite::query()->findOrFail($worksite_id);

        $worksite->loadMissing(self::SHOW_RELATIONS);

        return ShowWorksiteResource::make($worksite)->response();
    }

    public function store(WorksiteRequest $worksiteRequest): JsonResponse
    {
        $validated = $worksiteRequest->safe()->toArray();

        /** @var Worksite $createdWorksite */
        $createdWorksite = DB::transaction(
            fn () => $this->worksiteService->store(
                WorksiteDto::fromArray($validated)
            )
        );

        $createdWorksite->loadMissing(self::SHOW_RELATIONS);

        return ShowWorksiteResource::make($createdWorksite)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);

    }

    public function update(
        int $worksite_id,
        WorksiteRequest $worksiteRequest
    ): JsonResponse {
        $validated = $worksiteRequest->safe()->toArray();

        /** @var Worksite $worksite */
        $worksite = Worksite::query()->findOrFail($worksite_id);

        $updateWorksite = DB::transaction(
            fn () => $this->worksiteService->update(
                $worksite,
                WorksiteDto::fromArray($validated)
            )
        );
        $updateWorksite->loadMissing(self::SHOW_RELATIONS);

        return ShowWorksiteResource::make($updateWorksite)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function updateStatus(
        int $worksite_id,
        UpdateWorksiteStatusRequest $updateWorksiteStatusRequest
    ): JsonResponse {
        /** @var Worksite $worksite */
        $worksite = Worksite::query()->findOrFail($worksite_id);

        $updatedWorksite = DB::transaction(
            fn () => $this->worksiteService->updateStatus(
                $worksite,
                WorksiteStatus::from((string) $updateWorksiteStatusRequest->validated('worksite_status'))
            )
        );

        $updatedWorksite->loadMissing('client');

        return IndexWorksiteResource::make($updatedWorksite)->response();
    }

    public function summary(int $worksite_id): JsonResponse
    {
        /** @var Worksite $worksite */
        $worksite = Worksite::query()->findOrFail($worksite_id);

        return WorksiteSummaryResource::make(
            $this->worksiteSummaryService->summarize($worksite)
        )->response();
    }

    public function destroy(int $worksite_id): JsonResponse
    {
        /** @var Worksite $worksite */
        $worksite = Worksite::query()->findOrFail($worksite_id);
        $worksite->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
