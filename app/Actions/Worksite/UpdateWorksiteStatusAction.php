<?php

declare(strict_types=1);

namespace App\Actions\Worksite;

use App\Enums\WorksiteStatus;
use App\Models\Worksite;

final class UpdateWorksiteStatusAction
{
    public function __invoke(
        Worksite $worksite,
        WorksiteStatus $worksiteStatus,
    ): Worksite {
        $worksite->update([
            'worksite_status' => $worksiteStatus,
        ]);

        return $worksite->refresh();
    }
}
