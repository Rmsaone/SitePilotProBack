<?php

declare(strict_types=1);

namespace App\Actions\Invoice;

use App\Dto\InvoicePaymentDto;
use App\Enums\PaymentStatus;
use App\Models\InvoiceClient;

final class UpdateInvoicePaymentAction
{
    public function __invoke(
        InvoiceClient $invoiceClient,
        InvoicePaymentDto $invoicePaymentDto,
    ): InvoiceClient {
        $received = in_array(
            $invoicePaymentDto->paymentStatus,
            [PaymentStatus::Paid, PaymentStatus::Partial],
            true
        );

        $invoiceClient->update([
            'payment_status' => $invoicePaymentDto->paymentStatus,
            // A payment date only makes sense once money was received.
            'payment_date' => $received ? $invoicePaymentDto->paymentDate : null,
            'payment_method' => $invoicePaymentDto->paymentMethod ?? $invoiceClient->payment_method,
        ]);

        return $invoiceClient->refresh();
    }
}
