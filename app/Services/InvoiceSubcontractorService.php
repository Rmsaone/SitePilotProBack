<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InvoiceSubcontractor;
use Override;

class InvoiceSubcontractorService extends PurchaseInvoiceService
{
    #[Override]
    protected function modelClass(): string
    {
        return InvoiceSubcontractor::class;
    }
}
