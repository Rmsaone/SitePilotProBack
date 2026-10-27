<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InvoiceOther;
use Override;

class InvoiceOtherService extends PurchaseInvoiceService
{
    #[Override]
    protected function modelClass(): string
    {
        return InvoiceOther::class;
    }
}
