<?php

declare(strict_types=1);

namespace App\Http\Resources\Worksite;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property array<string,mixed> $resource
 */
final class WorksiteSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
