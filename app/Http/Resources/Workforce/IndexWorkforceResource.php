<?php

declare(strict_types=1);

namespace App\Http\Resources\Workforce;

use App\Models\Workforce;
use Cknow\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property Workforce $resource
 */
class IndexWorkforceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var Money|null $cost_hr_working */
        $cost_hr_working = $this->resource->cost_hr_working;
        /** @var Money|null $additional_costs */
        $additional_costs = $this->resource->additional_costs;
        /** @var Money|null $total_gross_cost */
        $total_gross_cost = $this->resource->total_gross_cost;

        return [
            'id' => $this->resource->id,
            'worksite_id' => $this->resource->worksite_id,
            'code' => $this->resource->code,
            'worker' => $this->resource->worker,
            'hr_working' => $this->resource->hr_working,
            'hr_rate' => $this->resource->hr_rate,
            'cost_hr_working' => $cost_hr_working,
            'additional_costs' => $additional_costs,
            'total_gross_cost' => $total_gross_cost,
        ];
    }
}
