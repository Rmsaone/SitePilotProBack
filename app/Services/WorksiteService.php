<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Worksite\CreateWorksiteAction;
use App\Actions\Worksite\UpdateWorksiteAction;
use App\Actions\Worksite\UpdateWorksiteStatusAction;
use App\Dto\WorksiteDto;
use App\Dto\WorksiteFilterDto;
use App\Enums\WorksiteStatus;
use App\Models\Worksite;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class WorksiteService
{
    public function list(WorksiteFilterDto $worksiteFilterDto): LengthAwarePaginator
    {
        return Worksite::query()
            ->with('client')
            ->when($worksiteFilterDto->search, function (Builder $query, string $search): void {
                $query->where(function (EloquentBuilder $query) use ($search): void {
                    $query->whereHas('client', function (Builder $query) use ($search): void {
                        $query->whereLike('company', "%{$search}%")
                            ->orWhereLike('contact_name', "%{$search}%");
                    })
                        ->orWhereLike('name_worksite', "%{$search}%")
                        ->orWhereLike('city', "%{$search}%");
                });
            })
            ->when(
                $worksiteFilterDto->worksiteStatus,
                fn (Builder $query, WorksiteStatus $status) => $query->where('worksite_status', $status)
            )
            ->orderBy($worksiteFilterDto->sort, $worksiteFilterDto->direction)
            ->orderByDesc('id')
            ->paginate($worksiteFilterDto->perPage);
    }

    public function store(WorksiteDto $worksiteDto): Worksite
    {
        /** @var Worksite $createWorksite */
        $createWorksite = app()->call(CreateWorksiteAction::class, [
            'worksiteDto' => $worksiteDto,
        ]);

        $createWorksite->refresh();

        return $createWorksite;
    }

    public function update(
        Worksite $worksite,
        WorksiteDto $worksiteDto
    ): Worksite {
        /** @var Worksite $updateWorksite */
        $updateWorksite = app()->call(UpdateWorksiteAction::class, [
            'worksite' => $worksite,
            'worksiteDto' => $worksiteDto,
        ]);

        $updateWorksite->refresh();

        return $updateWorksite;
    }

    public function updateStatus(
        Worksite $worksite,
        WorksiteStatus $worksiteStatus
    ): Worksite {
        /** @var Worksite $updateWorksite */
        $updateWorksite = app()->call(UpdateWorksiteStatusAction::class, [
            'worksite' => $worksite,
            'worksiteStatus' => $worksiteStatus,
        ]);

        return $updateWorksite;
    }

    public function destroy(Worksite $worksite): void
    {
        $worksite->delete();
    }
}
