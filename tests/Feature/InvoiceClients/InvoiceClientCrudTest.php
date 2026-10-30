<?php

declare(strict_types=1);

namespace Tests\Feature\InvoiceClients;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Enums\TvaRate;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Worksite;
use Cknow\Money\Money;
use Database\Factories\ClientFactory;
use Database\Factories\InvoiceClientFactory;
use Database\Factories\ProductLineFactory;
use Database\Factories\QuoteFactory;
use Database\Factories\WorksiteFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceClientCrudTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    private Worksite $worksite;

    private Quote $quote;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUser();
        $this->client = ClientFactory::new()->createOne(['company' => 'SCI Les Acacias']);
        $this->worksite = WorksiteFactory::new()->createOne(['client_id' => $this->client->id]);
        $this->quote = QuoteFactory::new()->createOne([
            'client_id' => $this->client->id,
            'quote_status' => QuoteStatus::Accept,
            'subtotal_ht' => Money::EUR(10_000_00),
            'tva_rate' => TvaRate::Taux10,
        ]);
        $this->quote->worksites()->attach($this->worksite->id);
        ProductLineFactory::new()->count(2)->create(['quote_id' => $this->quote->id]);
    }

    public function test_should_convert_an_accepted_quote_into_an_invoice(): void
    {
        $response = $this->postJson("/api/v1/quotes/{$this->quote->id}/invoice", ['delivery_date' => '2026-10-28']);

        $response->assertStatus(201)
            ->assertJsonPath('data.number_invoice', 'FAC-2026-001')
            ->assertJsonPath('data.total_ht.amount', '1000000')
            ->assertJsonPath('data.tva_amount.amount', '100000')
            ->assertJsonPath('data.total_ttc.amount', '1100000')
            ->assertJsonPath('data.payment_status', PaymentStatus::Pending->value)
            ->assertJsonPath('data.worksite.id', $this->worksite->id)
            ->assertJsonPath('data.quote.id', $this->quote->id)
            ->assertJsonCount(2, 'data.product_lines');
    }

    public function test_should_refuse_to_convert_a_quote_twice(): void
    {
        $this->postJson("/api/v1/quotes/{$this->quote->id}/invoice")->assertStatus(201);

        $this->postJson("/api/v1/quotes/{$this->quote->id}/invoice")
            ->assertStatus(422)
            ->assertJsonValidationErrors('quote');
    }

    public function test_should_refuse_to_convert_a_quote_that_is_not_accepted(): void
    {
        $this->quote->update(['quote_status' => QuoteStatus::Send]);

        $this->postJson("/api/v1/quotes/{$this->quote->id}/invoice")
            ->assertStatus(422)
            ->assertJsonValidationErrors('quote');
    }

    public function test_should_require_a_worksite_when_the_quote_has_none(): void
    {
        $this->quote->worksites()->detach();

        $this->postJson("/api/v1/quotes/{$this->quote->id}/invoice")
            ->assertStatus(422)
            ->assertJsonValidationErrors('worksite_id');

        $this->postJson("/api/v1/quotes/{$this->quote->id}/invoice", ['worksite_id' => $this->worksite->id])
            ->assertStatus(201);
    }

    public function test_should_reject_a_worksite_of_another_client(): void
    {
        $other = WorksiteFactory::new()->createOne();

        $this->postJson("/api/v1/quotes/{$this->quote->id}/invoice", ['worksite_id' => $other->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('worksite_id');
    }

    public function test_should_filter_invoices(): void
    {
        InvoiceClientFactory::new()->createOne([
            'client_id' => $this->client->id,
            'worksite_id' => $this->worksite->id,
            'number_invoice' => 'FAC-2026-010',
            'payment_status' => PaymentStatus::Overdue,
        ]);
        InvoiceClientFactory::new()->createOne(['number_invoice' => 'FAC-2026-011', 'payment_status' => PaymentStatus::Paid]);

        $this->getJson('/api/v1/invoice_clients?search=acacias')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/invoice_clients?search=011')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/invoice_clients?payment_status=overdue')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.number_invoice', 'FAC-2026-010');
        $this->getJson("/api/v1/invoice_clients?worksite_id={$this->worksite->id}")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/invoice_clients?client_id={$this->client->id}")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_should_show_an_invoice_with_client_address(): void
    {
        $invoiceId = $this->postJson("/api/v1/quotes/{$this->quote->id}/invoice")->json('data.id');

        $this->getJson("/api/v1/invoice_clients/{$invoiceId}")
            ->assertOk()
            ->assertJsonPath('data.client.city', $this->client->city)
            ->assertJsonCount(2, 'data.product_lines');
    }

    public function test_should_record_a_payment(): void
    {
        $invoiceId = $this->postJson("/api/v1/quotes/{$this->quote->id}/invoice")->json('data.id');

        $this->patchJson("/api/v1/invoice_clients/{$invoiceId}/payment", ['payment_status' => PaymentStatus::Paid->value])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_date', 'payment_method']);

        $this->patchJson("/api/v1/invoice_clients/{$invoiceId}/payment", [
            'payment_status' => PaymentStatus::Paid->value,
            'payment_date' => '2026-11-15',
            'payment_method' => PaymentMethod::BankTransfer->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.payment_status_title', 'Paiement reçu')
            ->assertJsonPath('data.payment_date', '2026-11-15')
            ->assertJsonPath('data.payment_method', PaymentMethod::BankTransfer->value);

        $this->patchJson("/api/v1/invoice_clients/{$invoiceId}/payment", ['payment_status' => PaymentStatus::Overdue->value])
            ->assertOk()
            ->assertJsonPath('data.payment_date', null);
    }

    public function test_should_soft_delete_an_invoice(): void
    {
        $invoice = InvoiceClientFactory::new()->createOne();

        $this->deleteJson("/api/v1/invoice_clients/{$invoice->id}")->assertNoContent();

        $this->assertSoftDeleted('invoice_clients', ['id' => $invoice->id]);
    }

    public function test_should_return_404_for_unknown_invoice(): void
    {
        $this->getJson('/api/v1/invoice_clients/999999')->assertNotFound();
        $this->postJson('/api/v1/quotes/999999/invoice')->assertNotFound();
    }
}
