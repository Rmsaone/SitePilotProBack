<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\PurchaseInvoice\CreatePurchaseInvoiceAction;
use App\Actions\PurchaseInvoice\UpdatePurchaseInvoiceAction;
use App\Dto\PurchaseInvoiceDto;
use App\Models\InvoiceOther;
use App\Models\InvoiceSubcontractor;
use App\Models\InvoiceSupplier;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Common logic for supplier, subcontractor and other invoices.
 */
abstract class PurchaseInvoiceService
{
    /**
     * @return class-string<InvoiceOther|InvoiceSubcontractor|InvoiceSupplier>
     */
    abstract protected function modelClass(): string;

    public function list(?int $worksiteId = null, int $perPage = 20): LengthAwarePaginator
    {
        return ($this->modelClass())::query()
            ->with('worksite')
            ->when($worksiteId, fn (Builder $query, int $id) => $query->where('worksite_id', $id))
            ->orderByDesc('delivery_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function store(PurchaseInvoiceDto $purchaseInvoiceDto): InvoiceSupplier|InvoiceSubcontractor|InvoiceOther
    {
        /** @var InvoiceOther|InvoiceSubcontractor|InvoiceSupplier $invoice */
        $invoice = app()->call(CreatePurchaseInvoiceAction::class, [
            'modelClass' => $this->modelClass(),
            'purchaseInvoiceDto' => $purchaseInvoiceDto,
        ]);

        return $invoice;
    }

    public function update(
        InvoiceSupplier|InvoiceSubcontractor|InvoiceOther $invoice,
        PurchaseInvoiceDto $purchaseInvoiceDto
    ): InvoiceSupplier|InvoiceSubcontractor|InvoiceOther {
        /** @var InvoiceOther|InvoiceSubcontractor|InvoiceSupplier $updatedInvoice */
        $updatedInvoice = app()->call(UpdatePurchaseInvoiceAction::class, [
            'invoice' => $invoice,
            'purchaseInvoiceDto' => $purchaseInvoiceDto,
        ]);

        return $updatedInvoice;
    }

    public function destroy(InvoiceSupplier|InvoiceSubcontractor|InvoiceOther $invoice): void
    {
        $invoice->delete();
    }
}
