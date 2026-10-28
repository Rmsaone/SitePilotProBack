<?php

declare(strict_types=1);

namespace App\Actions\PurchaseInvoice;

use App\Dto\PurchaseInvoiceDto;
use App\Models\InvoiceOther;
use App\Models\InvoiceSubcontractor;
use App\Models\InvoiceSupplier;

/**
 * Creates a supplier, subcontractor or other invoice: the three tables share the same columns.
 */
final class CreatePurchaseInvoiceAction
{
    /**
     * @param  class-string<InvoiceOther|InvoiceSubcontractor|InvoiceSupplier>  $modelClass
     */
    public function __invoke(
        string $modelClass,
        PurchaseInvoiceDto $purchaseInvoiceDto,
    ): InvoiceSupplier|InvoiceSubcontractor|InvoiceOther {
        /** @var InvoiceOther|InvoiceSubcontractor|InvoiceSupplier $invoice */
        $invoice = $modelClass::query()->create([
            'worksite_id' => $purchaseInvoiceDto->worksiteId,
            'delivery_date' => $purchaseInvoiceDto->deliveryDate,
            'number_invoice' => $purchaseInvoiceDto->numberInvoice,
            'invoice_description' => $purchaseInvoiceDto->invoiceDescription,
            'purchase_price' => $purchaseInvoiceDto->purchasePrice,
            'cost_price' => $purchaseInvoiceDto->costPrice,
        ]);

        StorePurchaseInvoiceAttachment::store($invoice, $purchaseInvoiceDto->attachment);

        return $invoice;
    }
}
