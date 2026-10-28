<?php

declare(strict_types=1);

namespace App\Actions\PurchaseInvoice;

use App\Dto\PurchaseInvoiceDto;
use App\Models\InvoiceOther;
use App\Models\InvoiceSubcontractor;
use App\Models\InvoiceSupplier;

final class UpdatePurchaseInvoiceAction
{
    public function __invoke(
        InvoiceSupplier|InvoiceSubcontractor|InvoiceOther $invoice,
        PurchaseInvoiceDto $purchaseInvoiceDto,
    ): InvoiceSupplier|InvoiceSubcontractor|InvoiceOther {
        $invoice->update([
            'worksite_id' => $purchaseInvoiceDto->worksiteId,
            'delivery_date' => $purchaseInvoiceDto->deliveryDate,
            'number_invoice' => $purchaseInvoiceDto->numberInvoice,
            'invoice_description' => $purchaseInvoiceDto->invoiceDescription,
            'purchase_price' => $purchaseInvoiceDto->purchasePrice,
            'cost_price' => $purchaseInvoiceDto->costPrice,
        ]);

        StorePurchaseInvoiceAttachment::store($invoice, $purchaseInvoiceDto->attachment);

        return $invoice->refresh();
    }
}
