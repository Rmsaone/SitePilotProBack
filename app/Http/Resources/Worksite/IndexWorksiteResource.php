<?php

declare(strict_types=1);

namespace App\Http\Resources\Worksite;

use App\Http\Resources\Client\IndexClientResource;
use App\Models\Worksite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property Worksite $resource
 */
final class IndexWorksiteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'client' => $this
                ->whenLoaded(
                    'client',
                    fn () => IndexClientResource::make($this->resource->client)),
            'code' => $this->resource->code,
            'name_worksite' => $this->resource->name_worksite,
            'start_date' => $this->resource->start_date,
            'end_date' => $this->resource->end_date,
            'worksite_priority' => $this->resource->worksite_priority,
            'worksite_status' => $this->resource->worksite_status,
            'city' => $this->resource->city,
            'zip_code' => $this->resource->zip_code,

        ];
    }
}
