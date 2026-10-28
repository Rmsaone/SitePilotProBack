<?php

declare(strict_types=1);

namespace App\Actions\Invoice;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Models\InvoiceClient;
use App\Models\Quote;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Converts an accepted quote into a client invoice (one invoice per quote).
 */
final class CreateInvoiceFromQuoteAction
{
    /**
     * @throws ValidationException
     */
    public function __invoke(
        Quote $quote,
        ?int $worksiteId = null,
        ?Carbon $deliveryDate = null,
        ?string $invoiceDescription = null,
    ): InvoiceClient {
        if ($quote->quote_status !== QuoteStatus::Accept) {
            throw ValidationException::withMessages([
                'quote' => 'Seul un devis accepté peut être converti en facture.',
            ]);
        }

        if ($quote->invoicesClients()->exists()) {
            throw ValidationException::withMessages([
                'quote' => 'Ce devis a déjà été converti en facture.',
            ]);
        }

        $worksiteId ??= $quote->worksites()->value('worksites.id');

        if ($worksiteId === null) {
            throw ValidationException::withMessages([
                'worksite_id' => 'Sélectionnez le chantier à facturer.',
            ]);
        }

        $deliveryDate ??= Carbon::now();

        /** @var InvoiceClient $invoice */
        $invoice = InvoiceClient::query()->create([
            'client_id' => $quote->client_id,
            'worksite_id' => $worksiteId,
            'quote_id' => $quote->id,
            'delivery_date' => $deliveryDate,
            'number_invoice' => $this->nextNumber($deliveryDate->year),
            'invoice_description' => $invoiceDescription ?? "Facture du devis {$quote->quote_number}",
            'total_ht' => $quote->subtotal_ht,
            'tva_rate' => $quote->tva_rate,
            'payment_status' => PaymentStatus::Pending,
            'payment_method' => PaymentMethod::Other,
        ]);

        return $invoice;
    }

    /**
     * FAC-{year}-{sequence}, the sequence restarts every year.
     */
    private function nextNumber(int $year): string
    {
        $prefix = "FAC-{$year}-";

        $last = InvoiceClient::withTrashed()
            ->where('number_invoice', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('number_invoice')
            ->map(fn (string $number): int => (int) substr($number, strlen($prefix)))
            ->max();

        return $prefix.str_pad((string) (((int) $last) + 1), 3, '0', STR_PAD_LEFT);
    }
}
