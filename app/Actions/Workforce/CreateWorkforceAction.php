<?php

declare(strict_types=1);

namespace App\Actions\Workforce;

use App\Dto\WorkforceDto;
use App\Models\Workforce;

final class CreateWorkforceAction
{
    public function __invoke(
        WorkforceDto $workforceDto,
    ): Workforce {
        return Workforce::query()->create([
            'worksite_id' => $workforceDto->worksiteId,
            ...WorkforceCosts::fromDto($workforceDto),
        ]);
    }
}
