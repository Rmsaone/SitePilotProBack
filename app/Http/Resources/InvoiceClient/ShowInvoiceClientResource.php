<?php

declare(strict_types=1);

namespace App\Http\Resources\InvoiceClient;

use App\Http\Resources\Client\ShowClientResource;
use App\Http\Resources\ProductLine\IndexProductLineResource;
use App\Models\Quote;
use Illuminate\Http\Request;
use Override;

/**
 * Invoice document: the lines are the ones of the converted quote.
 */
final class ShowInvoiceClientResource extends InvoiceClientResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'client' => $this
                ->whenLoaded(
                    'client',
                    fn () => ShowClientResource::make($this->resource->client)),
            'product_lines' => $this->resource->quote instanceof Quote
                ? IndexProductLineResource::collection(
                    $this->resource->quote->productLines->sortBy('sort_order')->values()
                )
                : [],
        ];
    }
}
