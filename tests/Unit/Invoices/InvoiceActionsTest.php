<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices;

use App\Actions\Invoice\CreateInvoiceFromQuoteAction;
use App\Actions\Invoice\UpdateInvoicePaymentAction;
use App\Dto\InvoicePaymentDto;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\Worksite;
use Database\Factories\InvoiceClientFactory;
use Database\Factories\QuoteFactory;
use Database\Factories\WorksiteFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * @internal
 */
#[CoversClass(CreateInvoiceFromQuoteAction::class)]
#[CoversClass(UpdateInvoicePaymentAction::class)]
#[CoversClass(InvoicePaymentDto::class)]
final class InvoiceActionsTest extends TestCase
{
    use DatabaseTransactions;

    private Quote $quote;

    private Worksite $worksite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quote = QuoteFactory::new()->createOne(['quote_status' => QuoteStatus::Accept]);
        $this->worksite = WorksiteFactory::new()->createOne(['client_id' => $this->quote->client_id]);
    }

    #[TestDox(
        'Given an invoice numbered for the year
        When I convert a quote
        Then the next invoice number should follow the sequence'
    )]
    public function test_should_generate_next_invoice_number(): void
    {
        InvoiceClientFactory::new()->createOne(['number_invoice' => 'FAC-2032-041']);

        $invoice = (new CreateInvoiceFromQuoteAction())($this->quote, $this->worksite->id, Carbon::parse('2032-03-01'));

        $this->assertSame('FAC-2032-042', $invoice->number_invoice);
        $this->assertSame($this->quote->client_id, $invoice->client_id);
        $this->assertSame($this->quote->subtotal_ht->getAmount(), $invoice->total_ht->getAmount());
    }

    #[TestDox(
        'Given a paid invoice
        When the payment is set back to pending
        Then the payment date should be cleared'
    )]
    public function test_should_clear_payment_date_when_not_received(): void
    {
        $invoice = InvoiceClientFactory::new()->createOne([
            'payment_status' => PaymentStatus::Paid,
            'payment_date' => '2026-10-01',
        ]);

        $result = (new UpdateInvoicePaymentAction())($invoice, new InvoicePaymentDto(PaymentStatus::Pending, Carbon::parse('2026-10-02'), null));

        $this->assertSame(PaymentStatus::Pending, $result->payment_status);
        $this->assertNull($result->payment_date);
    }

    #[TestDox(
        'Given a received payment
        When I call UpdateInvoicePaymentAction
        Then date and method should be stored'
    )]
    public function test_should_store_payment_date_and_method(): void
    {
        $invoice = InvoiceClientFactory::new()->createOne(['payment_status' => PaymentStatus::Pending]);

        $result = (new UpdateInvoicePaymentAction())($invoice, InvoicePaymentDto::fromArray([
            'payment_status' => 'partial',
            'payment_date' => '2026-11-02',
            'payment_method' => 'check',
        ]));

        $this->assertSame('2026-11-02', $result->payment_date?->toDateString());
        $this->assertSame(PaymentMethod::Check, $result->payment_method);
    }
}
