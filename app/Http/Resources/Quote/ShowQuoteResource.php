<?php

declare(strict_types=1);

namespace App\Http\Resources\Quote;

use App\Http\Resources\Client\ShowClientResource;
use App\Http\Resources\ProductLine\IndexProductLineResource;
use App\Models\Quote;
use App\Models\Worksite;
use Illuminate\Http\Request;
use Override;

/**
 * @property Quote $resource
 */
class ShowQuoteResource extends IndexQuoteResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'tva_rate_title' => $this->resource->tva_rate->title(),
            'client' => $this
                ->whenLoaded(
                    'client',
                    fn () => ShowClientResource::make($this->resource->client)),
            'worksites' => $this
                ->whenLoaded(
                    'worksites',
                    fn () => $this->resource->worksites->map(fn (Worksite $worksite): array => [
                        'id' => $worksite->id,
                        'name_worksite' => $worksite->name_worksite,
                    ])),
            'product_lines' => $this
                ->whenLoaded(
                    'productLines',
                    fn () => IndexProductLineResource::collection(
                        $this->resource->productLines->sortBy('sort_order')->values()
                    )),
            'product_lines_count' => $this->whenCounted('productLines'),
        ];
    }
}
