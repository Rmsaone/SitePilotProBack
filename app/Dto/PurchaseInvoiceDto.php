<?php

declare(strict_types=1);

namespace App\Dto;

use Cknow\Money\Money;
use Illuminate\Support\Carbon;

/**
 * Shared by supplier, subcontractor and other invoices (same table shape).
 * Prices are received in euros and stored in cents.
 */
final readonly class PurchaseInvoiceDto
{
    public function __construct(
        public int $worksiteId,
        public Carbon $deliveryDate,
        public string $numberInvoice,
        public string $invoiceDescription,
        public Money $purchasePrice,
        public Money $costPrice,
    ) {
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            worksiteId: (int) $data['worksite_id'],
            deliveryDate: Carbon::parse($data['delivery_date']),
            numberInvoice: (string) $data['number_invoice'],
            invoiceDescription: (string) $data['invoice_description'],
            purchasePrice: Money::EUR((int) round((float) $data['purchase_price'] * 100)),
            costPrice: Money::EUR((int) round((float) $data['cost_price'] * 100)),
        );
    }
}
