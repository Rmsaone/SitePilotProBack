<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Enums\WorksiteStatus;
use Cknow\Money\Money;
use Database\Factories\InvoiceClientFactory;
use Database\Factories\InvoiceSupplierFactory;
use Database\Factories\QuoteFactory;
use Database\Factories\WorksiteFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-15 10:00:00');
    }

    public function test_should_return_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/dashboard')->assertStatus(401);
    }

    public function test_should_reject_an_unknown_period(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/v1/dashboard?period=decade')
            ->assertStatus(422)
            ->assertJsonValidationErrors('period');
    }

    public function test_should_compute_monthly_counters_and_trends(): void
    {
        $this->actingAsUser();

        WorksiteFactory::new()->count(2)->create(['worksite_status' => WorksiteStatus::InProgress]);
        $finished = WorksiteFactory::new()->createOne(['worksite_status' => WorksiteStatus::Finished]);

        QuoteFactory::new()->count(2)->create(['quote_status' => QuoteStatus::Accept, 'issue_date' => '2026-10-05']);
        QuoteFactory::new()->createOne(['quote_status' => QuoteStatus::Accept, 'issue_date' => '2026-09-10']);
        QuoteFactory::new()->createOne(['quote_status' => QuoteStatus::Draft, 'issue_date' => '2026-10-07']);

        InvoiceClientFactory::new()->createOne(['worksite_id' => $finished->id, 'client_id' => $finished->client_id, 'delivery_date' => '2026-10-02', 'total_ht' => Money::EUR(3_000_00), 'payment_status' => PaymentStatus::Paid]);
        InvoiceClientFactory::new()->createOne(['worksite_id' => $finished->id, 'client_id' => $finished->client_id, 'delivery_date' => '2026-10-09', 'total_ht' => Money::EUR(1_000_00), 'payment_status' => PaymentStatus::Canceled]);
        InvoiceClientFactory::new()->createOne(['worksite_id' => $finished->id, 'client_id' => $finished->client_id, 'delivery_date' => '2026-09-20', 'total_ht' => Money::EUR(2_000_00), 'payment_status' => PaymentStatus::Pending]);

        $this->getJson('/api/v1/dashboard?period=month')
            ->assertOk()
            ->assertJsonPath('data.period_title', 'Ce mois')
            ->assertJsonPath('data.worksites_in_progress.value', 2)
            ->assertJsonPath('data.accepted_quotes.value', 2)
            ->assertJsonPath('data.accepted_quotes.trend', 100)
            ->assertJsonPath('data.created_quotes.value', 3)
            ->assertJsonPath('data.invoices.value', 2)
            ->assertJsonPath('data.revenue.amount.amount', '300000')
            ->assertJsonPath('data.revenue.trend', 50);
    }

    public function test_should_not_compute_trends_for_all_time(): void
    {
        $this->actingAsUser();

        QuoteFactory::new()->count(3)->create(['issue_date' => '2024-01-10']);

        $this->getJson('/api/v1/dashboard?period=all')
            ->assertOk()
            ->assertJsonPath('data.created_quotes.value', 3)
            ->assertJsonPath('data.created_quotes.trend', null)
            ->assertJsonPath('data.revenue.trend', null);
    }

    public function test_should_compute_remaining_to_invoice_on_active_worksites(): void
    {
        $this->actingAsUser();

        $active = WorksiteFactory::new()->createOne(['worksite_status' => WorksiteStatus::InProgress]);
        $quote = QuoteFactory::new()->createOne([
            'client_id' => $active->client_id,
            'quote_status' => QuoteStatus::Accept,
            'subtotal_ht' => Money::EUR(10_000_00),
        ]);
        $active->quotes()->attach($quote->id);
        InvoiceClientFactory::new()->createOne([
            'worksite_id' => $active->id,
            'client_id' => $active->client_id,
            'total_ht' => Money::EUR(4_000_00),
            'payment_status' => PaymentStatus::Paid,
        ]);

        $finished = WorksiteFactory::new()->createOne(['worksite_status' => WorksiteStatus::Finished]);
        $finishedQuote = QuoteFactory::new()->createOne(['quote_status' => QuoteStatus::Accept, 'subtotal_ht' => Money::EUR(5_000_00)]);
        $finished->quotes()->attach($finishedQuote->id);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.remaining_to_invoice.amount.amount', '600000')
            ->assertJsonPath('data.remaining_to_invoice.worksites', 1);
    }

    public function test_should_return_twelve_months_of_invoiced_and_spent_amounts(): void
    {
        $this->actingAsUser();

        InvoiceClientFactory::new()->createOne(['delivery_date' => '2026-10-02', 'total_ht' => Money::EUR(5_000_00), 'payment_status' => PaymentStatus::Paid]);
        InvoiceSupplierFactory::new()->createOne(['delivery_date' => '2026-10-05', 'cost_price' => Money::EUR(1_200_00)]);
        InvoiceSupplierFactory::new()->createOne(['delivery_date' => '2025-01-05', 'cost_price' => Money::EUR(999_00)]);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonCount(12, 'data.monthly')
            ->assertJsonPath('data.monthly.0.month', '2025-11')
            ->assertJsonPath('data.monthly.11.month', '2026-10')
            ->assertJsonPath('data.monthly.11.invoiced.amount', '500000')
            ->assertJsonPath('data.monthly.11.spent.amount', '120000');
    }
}
