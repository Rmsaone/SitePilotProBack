<?php

declare(strict_types=1);

namespace App\Http\Resources\InvoiceClient;

use App\Actions\Quote\SyncQuoteLinesAction;
use App\Http\Resources\Client\IndexClientResource;
use App\Models\InvoiceClient;
use App\Models\Quote;
use App\Models\Worksite;
use Cknow\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property InvoiceClient $resource
 */
class InvoiceClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var Money $totalHt */
        $totalHt = $this->resource->total_ht;
        $tvaAmount = SyncQuoteLinesAction::tva($totalHt, $this->resource->tva_rate);

        return [
            'id' => $this->resource->id,
            'delivery_date' => $this->resource->delivery_date->toDateString(),
            'number_invoice' => $this->resource->number_invoice,
            'invoice_description' => $this->resource->invoice_description,
            'total_ht' => $totalHt,
            'tva_rate' => $this->resource->tva_rate->value,
            'tva_rate_numeric' => $this->resource->tva_rate->numeric(),
            'tva_amount' => $tvaAmount,
            'total_ttc' => $totalHt->add($tvaAmount),
            'payment_date' => $this->resource->payment_date?->toDateString(),
            'payment_status' => $this->resource->payment_status->value,
            'payment_status_title' => $this->resource->payment_status->title(),
            'payment_method' => $this->resource->payment_method?->value,
            'payment_method_title' => $this->resource->payment_method?->title(),
            'client' => $this
                ->whenLoaded(
                    'client',
                    fn () => IndexClientResource::make($this->resource->client)),
            'worksite' => $this
                ->whenLoaded(
                    'worksite',
                    fn () => $this->resource->worksite instanceof Worksite
                        ? ['id' => $this->resource->worksite->id, 'name_worksite' => $this->resource->worksite->name_worksite]
                        : null),
            'quote' => $this
                ->whenLoaded(
                    'quote',
                    fn () => $this->resource->quote instanceof Quote
                        ? ['id' => $this->resource->quote->id, 'quote_number' => $this->resource->quote->quote_number]
                        : null),
        ];
    }
}
