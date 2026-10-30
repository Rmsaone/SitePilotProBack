<?php

declare(strict_types=1);

namespace App\Actions\Workforce;

use App\Dto\WorkforceDto;

/**
 * Derived costs are computed server-side so they always match hours × rate.
 */
final class WorkforceCosts
{
    /**
     * @return array<string,mixed>
     */
    public static function fromDto(WorkforceDto $workforceDto): array
    {
        $costHrWorking = $workforceDto->hrRate->multiply((string) $workforceDto->hrWorking);

        return [
            'code' => $workforceDto->code,
            'worker' => $workforceDto->worker,
            'hr_working' => $workforceDto->hrWorking,
            'hr_rate' => $workforceDto->hrRate,
            'cost_hr_working' => $costHrWorking,
            'additional_costs' => $workforceDto->additionalCosts,
            'total_gross_cost' => $costHrWorking->add($workforceDto->additionalCosts),
        ];
    }
}
