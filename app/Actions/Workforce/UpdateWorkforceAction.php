<?php

declare(strict_types=1);

namespace App\Actions\Workforce;

use App\Dto\WorkforceDto;
use App\Models\Workforce;

final class UpdateWorkforceAction
{
    public function __invoke(
        Workforce $workforce,
        WorkforceDto $workforceDto,
    ): Workforce {
        $workforce->update([
            'worksite_id' => $workforceDto->worksiteId,
            ...WorkforceCosts::fromDto($workforceDto),
        ]);

        return $workforce->refresh();
    }
}
