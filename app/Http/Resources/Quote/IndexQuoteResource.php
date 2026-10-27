<?php

declare(strict_types=1);

namespace App\Http\Resources\Quote;

use App\Http\Resources\Client\IndexClientResource;
use App\Models\Quote;
use App\Models\Worksite;
use Cknow\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property Quote $resource
 */
class IndexQuoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var Money|null $subtotalHt */
        $subtotalHt = $this->resource->subtotal_ht;
        /** @var Money|null $tvaAmount */
        $tvaAmount = $this->resource->tva_amount;
        /** @var Money|null $totalTtc */
        $totalTtc = $this->resource->total_ttc;

        return [
            'id' => $this->resource->id,
            'quote_number' => $this->resource->quote_number,
            'issue_date' => $this->resource->issue_date->toDateString(),
            'validity_date' => $this->resource->validity_date->toDateString(),
            'quote_status' => $this->resource->quote_status->value,
            'quote_status_title' => $this->resource->quote_status->title(),
            'subtotal_ht' => $subtotalHt,
            'tva_rate' => $this->resource->tva_rate->value,
            'tva_rate_numeric' => $this->resource->tva_rate->numeric(),
            'tva_amount' => $tvaAmount,
            'total_ttc' => $totalTtc,
            'client' => $this
                ->whenLoaded(
                    'client',
                    fn () => IndexClientResource::make($this->resource->client)),
            'worksites' => $this
                ->whenLoaded(
                    'worksites',
                    fn () => $this->resource->worksites->map(fn (Worksite $worksite): array => [
                        'id' => $worksite->id,
                        'name_worksite' => $worksite->name_worksite,
                    ])),
        ];
    }
}
