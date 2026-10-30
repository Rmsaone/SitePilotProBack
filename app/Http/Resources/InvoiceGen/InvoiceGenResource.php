<?php

declare(strict_types=1);

namespace App\Http\Resources\InvoiceGen;

use App\Http\Resources\Worksite\IndexWorksiteResource;
use App\Models\InvoiceOther;
use App\Models\InvoiceSubcontractor;
use App\Models\InvoiceSupplier;
use Cknow\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property InvoiceOther|InvoiceSubcontractor|InvoiceSupplier $resource
 */
class InvoiceGenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var Money|null $purchasePrice */
        $purchasePrice = $this->resource->purchase_price;
        /** @var Money|null $cost_price */
        $cost_price = $this->resource->cost_price;

        return [
            'id' => $this->resource->id,
            'worksite_id' => $this->resource->worksite_id,
            'delivery_date' => $this->resource->delivery_date->toDateString(),
            'number_invoice' => $this->resource->number_invoice,
            'invoice_description' => $this->resource->invoice_description,
            'purchase_price' => $purchasePrice,
            'cost_price' => $cost_price,
            'has_attachment' => $this->resource->attachment_path !== null,
            'attachment_name' => $this->resource->attachment_name,
            'worksite' => $this
                ->whenLoaded(
                    'worksite',
                    fn () => IndexWorksiteResource::make($this->resource->worksite)),
        ];
    }
}
