<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Invoice\CreateInvoiceFromQuoteAction;
use App\Actions\Invoice\UpdateInvoicePaymentAction;
use App\Dto\InvoiceClientFilterDto;
use App\Dto\InvoicePaymentDto;
use App\Enums\PaymentStatus;
use App\Models\InvoiceClient;
use App\Models\Quote;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class InvoiceClientService
{
    public function list(InvoiceClientFilterDto $invoiceClientFilterDto): LengthAwarePaginator
    {
        return InvoiceClient::query()
            ->with('client', 'worksite', 'quote')
            ->when($invoiceClientFilterDto->search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('client', function (Builder $query) use ($search): void {
                        $query->whereLike('company', "%{$search}%")
                            ->orWhereLike('contact_name', "%{$search}%");
                    })
                        ->orWhereLike('number_invoice', "%{$search}%");
                });
            })
            ->when($invoiceClientFilterDto->worksiteId, fn (Builder $query, int $id) => $query->where('worksite_id', $id))
            ->when($invoiceClientFilterDto->clientId, fn (Builder $query, int $id) => $query->where('client_id', $id))
            ->when($invoiceClientFilterDto->paymentStatus, fn (Builder $query, PaymentStatus $status) => $query->where('payment_status', $status))
            ->orderByDesc('delivery_date')
            ->orderByDesc('id')
            ->paginate($invoiceClientFilterDto->perPage);
    }

    public function createFromQuote(
        Quote $quote,
        ?int $worksiteId,
        ?Carbon $deliveryDate,
        ?string $invoiceDescription,
    ): InvoiceClient {
        /** @var InvoiceClient $invoice */
        $invoice = app()->call(CreateInvoiceFromQuoteAction::class, [
            'quote' => $quote,
            'worksiteId' => $worksiteId,
            'deliveryDate' => $deliveryDate,
            'invoiceDescription' => $invoiceDescription,
        ]);

        return $invoice->refresh();
    }

    public function updatePayment(
        InvoiceClient $invoiceClient,
        InvoicePaymentDto $invoicePaymentDto
    ): InvoiceClient {
        /** @var InvoiceClient $updatedInvoice */
        $updatedInvoice = app()->call(UpdateInvoicePaymentAction::class, [
            'invoiceClient' => $invoiceClient,
            'invoicePaymentDto' => $invoicePaymentDto,
        ]);

        return $updatedInvoice;
    }

    public function destroy(InvoiceClient $invoiceClient): void
    {
        $invoiceClient->delete();
    }
}
