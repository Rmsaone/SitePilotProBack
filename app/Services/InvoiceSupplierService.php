<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InvoiceSupplier;
use Override;

class InvoiceSupplierService extends PurchaseInvoiceService
{
    #[Override]
    protected function modelClass(): string
    {
        return InvoiceSupplier::class;
    }
}
