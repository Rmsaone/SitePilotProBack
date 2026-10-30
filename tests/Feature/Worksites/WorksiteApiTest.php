<?php

declare(strict_types=1);

namespace Tests\Feature\Worksites;

use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Enums\WorksitePriority;
use App\Enums\WorksiteStatus;
use App\Models\Client;
use App\Models\User;
use Cknow\Money\Money;
use Database\Factories\ClientFactory;
use Database\Factories\InvoiceClientFactory;
use Database\Factories\InvoiceSupplierFactory;
use Database\Factories\QuoteFactory;
use Database\Factories\WorkforceFactory;
use Database\Factories\WorksiteFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorksiteApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->actingAsUser();
        $this->client = ClientFactory::new()->createOne(['company' => 'Mairie de Limoges']);
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_id' => $this->client->id,
            'name_worksite' => 'Rénovation Aile Ouest',
            'description' => 'Reprise de la toiture',
            'start_date' => '2026-11-01',
            'end_date' => '2027-03-31',
            'worksite_priority' => WorksitePriority::Medium->value,
            'worksite_status' => WorksiteStatus::Pending->value,
            'street' => '12 avenue des Fleurs',
            'city' => 'Limoges',
            'zip_code' => 87000,
            'country' => 'France',
        ], $overrides);
    }

    public function test_should_return_401_when_unauthenticated(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/worksites')->assertStatus(401);
    }

    public function test_should_search_worksites_by_name_or_client(): void
    {
        WorksiteFactory::new()->createOne(['name_worksite' => 'Villa Les Pins']);
        WorksiteFactory::new()->createOne(['client_id' => $this->client->id, 'name_worksite' => 'Gymnase']);
        WorksiteFactory::new()->createOne(['name_worksite' => 'Parking souterrain']);

        $this->getJson('/api/v1/worksites?search=villa')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name_worksite', 'Villa Les Pins');

        $this->getJson('/api/v1/worksites?search=limoges')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name_worksite', 'Gymnase');
    }

    public function test_should_filter_worksites_by_status(): void
    {
        WorksiteFactory::new()->count(2)->create(['worksite_status' => WorksiteStatus::InProgress]);
        WorksiteFactory::new()->createOne(['worksite_status' => WorksiteStatus::Finished]);

        $this->getJson('/api/v1/worksites?worksite_status=in_progress')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_should_filter_worksites_by_client(): void
    {
        WorksiteFactory::new()->count(2)->create(['client_id' => $this->client->id]);
        WorksiteFactory::new()->createOne();

        $this->getJson("/api/v1/worksites?client_id={$this->client->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_should_sort_worksites_by_start_date(): void
    {
        WorksiteFactory::new()->createOne(['name_worksite' => 'Tardif', 'start_date' => '2027-01-01']);
        WorksiteFactory::new()->createOne(['name_worksite' => 'Précoce', 'start_date' => '2025-01-01']);

        $this->getJson('/api/v1/worksites?sort=start_date&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.name_worksite', 'Précoce');
    }

    public function test_should_reject_unknown_sort_column(): void
    {
        $this->getJson('/api/v1/worksites?sort=password')
            ->assertStatus(422)
            ->assertJsonValidationErrors('sort');
    }

    public function test_should_create_worksite_linked_to_accepted_quote_and_responsible(): void
    {
        $quote = QuoteFactory::new()->createOne([
            'client_id' => $this->client->id,
            'quote_status' => QuoteStatus::Accept,
        ]);

        $response = $this->postJson('/api/v1/worksites', $this->payload([
            'quote_id' => $quote->id,
            'user_id' => $this->user->id,
        ]));

        $response->assertStatus(201)
            ->assertJsonPath('data.responsible.id', $this->user->id)
            ->assertJsonPath('data.quotes.0.id', $quote->id);

        $this->assertDatabaseHas('worksite_quote', ['quote_id' => $quote->id]);
        $this->assertDatabaseHas('worksite_user', ['user_id' => $this->user->id]);
    }

    public function test_should_require_a_quote_on_creation(): void
    {
        $this->postJson('/api/v1/worksites', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('quote_id');
    }

    public function test_should_reject_a_quote_that_is_not_accepted(): void
    {
        $quote = QuoteFactory::new()->createOne([
            'client_id' => $this->client->id,
            'quote_status' => QuoteStatus::Draft,
        ]);

        $this->postJson('/api/v1/worksites', $this->payload(['quote_id' => $quote->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('quote_id');
    }

    public function test_should_reject_a_quote_from_another_client(): void
    {
        $quote = QuoteFactory::new()->createOne(['quote_status' => QuoteStatus::Accept]);

        $this->postJson('/api/v1/worksites', $this->payload(['quote_id' => $quote->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('quote_id');
    }

    public function test_should_update_worksite_status(): void
    {
        $worksite = WorksiteFactory::new()->createOne(['worksite_status' => WorksiteStatus::InProgress]);

        $this->patchJson("/api/v1/worksites/{$worksite->id}/status", [
            'worksite_status' => WorksiteStatus::Finished->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.worksite_status', WorksiteStatus::Finished->value);

        $this->assertDatabaseHas('worksites', [
            'id' => $worksite->id,
            'worksite_status' => WorksiteStatus::Finished->value,
        ]);
    }

    public function test_should_reject_invalid_status(): void
    {
        $worksite = WorksiteFactory::new()->createOne();

        $this->patchJson("/api/v1/worksites/{$worksite->id}/status", ['worksite_status' => 'unknown'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('worksite_status');
    }

    public function test_should_return_worksite_financial_summary(): void
    {
        $worksite = WorksiteFactory::new()->createOne(['client_id' => $this->client->id]);
        $quote = QuoteFactory::new()->createOne([
            'client_id' => $this->client->id,
            'quote_status' => QuoteStatus::Accept,
            'subtotal_ht' => Money::EUR(10_000_00),
        ]);
        $worksite->quotes()->attach($quote->id);

        InvoiceClientFactory::new()->createOne([
            'worksite_id' => $worksite->id,
            'client_id' => $this->client->id,
            'total_ht' => Money::EUR(6_000_00),
            'payment_status' => PaymentStatus::Pending,
            'delivery_date' => now(),
        ]);
        InvoiceSupplierFactory::new()->createOne([
            'worksite_id' => $worksite->id,
            'purchase_price' => Money::EUR(2_000_00),
            'cost_price' => Money::EUR(2_500_00),
            'delivery_date' => now(),
        ]);
        WorkforceFactory::new()->createOne([
            'worksite_id' => $worksite->id,
            'cost_hr_working' => Money::EUR(1_000_00),
            'total_gross_cost' => Money::EUR(1_500_00),
        ]);

        $response = $this->getJson("/api/v1/worksites/{$worksite->id}/summary");

        $response->assertOk()
            ->assertJsonPath('data.market_amount.amount', '1000000')
            ->assertJsonPath('data.invoiced_amount.amount', '600000')
            ->assertJsonPath('data.remaining_to_invoice.amount', '400000')
            ->assertJsonPath('data.total_costs.purchase.amount', '300000')
            ->assertJsonPath('data.total_costs.cost.amount', '400000')
            ->assertJsonPath('data.remaining_to_spend.amount', '600000')
            ->assertJsonPath('data.gross_margin.amount.amount', '300000')
            ->assertJsonPath('data.gross_margin.rate', 50)
            ->assertJsonPath('data.net_margin.amount.amount', '200000')
            ->assertJsonPath('data.pending_invoices.count', 1)
            ->assertJsonCount(12, 'data.monthly')
            ->assertJsonPath('data.monthly.11.invoiced.amount', '600000')
            ->assertJsonPath('data.monthly.11.spent.amount', '250000');
    }

    public function test_should_return_404_summary_for_unknown_worksite(): void
    {
        $this->getJson('/api/v1/worksites/999999/summary')->assertNotFound();
    }
}
