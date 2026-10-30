<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Workforce\CreateWorkforceAction;
use App\Actions\Workforce\UpdateWorkforceAction;
use App\Dto\WorkforceDto;
use App\Models\Workforce;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkforceService
{
    public function list(?int $worksiteId = null, int $perPage = 15): LengthAwarePaginator
    {
        return Workforce::query()
            ->with('worksite')
            ->when($worksiteId, fn (Builder $query, int $id) => $query->where('worksite_id', $id))
            ->latest()
            ->paginate($perPage);
    }

    public function store(WorkforceDto $workforceDto): Workforce
    {
        /** @var Workforce $workforce */
        $workforce = app()->call(CreateWorkforceAction::class, [
            'workforceDto' => $workforceDto,
        ]);

        return $workforce;
    }

    public function update(Workforce $workforce, WorkforceDto $workforceDto): Workforce
    {
        /** @var Workforce $updatedWorkforce */
        $updatedWorkforce = app()->call(UpdateWorkforceAction::class, [
            'workforce' => $workforce,
            'workforceDto' => $workforceDto,
        ]);

        return $updatedWorkforce;
    }

    public function destroy(Workforce $workforce): void
    {
        $workforce->delete();
    }
}
