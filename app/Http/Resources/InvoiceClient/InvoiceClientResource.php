<?php

declare(strict_types=1);

namespace App\Http\Resources\InvoiceClient;

use App\Http\Resources\Client\IndexClientResource;
use App\Http\Resources\Quote\IndexQuoteResource;
use App\Http\Resources\Worksite\IndexWorksiteResource;
use App\Models\InvoiceClient;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Money\Money;
use Override;

/**
 * @property InvoiceClient $resource
 */
final class InvoiceClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var Money|null $totalHt */
        $totalHt = $this->resource->total_ht;

        return [
            'id' => $this->resource->id,
            'delivery_date' => $this->resource->delivery_date,
            'number_invoice' => $this->resource->number_invoice,
            'invoice_description' => $this->resource->invoice_description,
            'total_ht' => $totalHt,
            'tva_rate' => $this->resource->tva_rate->numeric(),
            'payment_date' => $this->resource->payment_date,
            'payment_status' => $this->resource->payment_status->value,
            'payment_status_title' => $this->resource->payment_status->title(),
            'payment_method' => $this->resource->payment_method?->title(),
            'client' => $this
                ->whenLoaded(
                    'client',
                    fn () => IndexClientResource::make($this->resource->client)),
            'worksite' => $this
                ->whenLoaded(
                    'worksite',
                    fn () => IndexWorksiteResource::make($this->resource->worksite)),
            'quote' => $this
                ->whenLoaded(
                    'quote',
                    fn () => $this->resource->quote instanceof Quote
                        ? IndexQuoteResource::make($this->resource->quote)
                        : null),
        ];
    }
}
