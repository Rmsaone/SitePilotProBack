<?php

declare(strict_types=1);

namespace Tests\Feature\Quotes;

use App\Enums\QuoteStatus;
use App\Enums\TvaRate;
use App\Enums\Unit;
use App\Models\Client;
use App\Models\Quote;
use Database\Factories\ClientFactory;
use Database\Factories\QuoteFactory;
use Database\Factories\WorksiteFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteCrudTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUser();
        $this->client = ClientFactory::new()->createOne(['company' => 'Mairie de Limoges']);
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_id' => $this->client->id,
            'issue_date' => '2026-10-28',
            'validity_date' => '2027-01-28',
            'quote_status' => QuoteStatus::Draft->value,
            'tva_rate' => TvaRate::Taux20->value,
            'lines' => [
                ['category' => 'Lot 1 — Gros œuvre', 'description' => 'Terrassement', 'quantity' => 120, 'unit' => Unit::M2->value, 'unit_price_ht' => 85],
                ['category' => 'Lot 1 — Gros œuvre', 'description' => 'Dalle béton', 'quantity' => 9.5, 'unit' => Unit::M2->value, 'unit_price_ht' => 110.4],
                ['category' => null, 'description' => 'Chef de chantier', 'quantity' => 10, 'unit' => Unit::Hour->value, 'unit_price_ht' => 55],
            ],
        ], $overrides);
    }

    public function test_should_create_quote_with_lines_and_totals(): void
    {
        $response = $this->postJson('/api/v1/quotes', $this->payload());

        // 120 × 85 + 9.5 × 110.40 + 10 × 55 = 10 200 + 1 048.80 + 550 = 11 798.80
        $response->assertStatus(201)
            ->assertJsonPath('data.quote_number', 'DEV-2026-001')
            ->assertJsonPath('data.subtotal_ht.amount', '1179880')
            ->assertJsonPath('data.tva_amount.amount', '235976')
            ->assertJsonPath('data.total_ttc.amount', '1415856')
            ->assertJsonPath('data.quote_status_title', 'Brouillon')
            ->assertJsonCount(3, 'data.product_lines')
            ->assertJsonPath('data.product_lines.1.quantity', 9.5)
            ->assertJsonPath('data.product_lines.1.total_ht.amount', '104880')
            ->assertJsonPath('data.product_lines.2.category', null);

        $this->assertDatabaseCount('product_lines', 3);
    }

    public function test_should_increment_quote_number_per_year(): void
    {
        $this->postJson('/api/v1/quotes', $this->payload())->assertStatus(201);
        $this->postJson('/api/v1/quotes', $this->payload())
            ->assertStatus(201)
            ->assertJsonPath('data.quote_number', 'DEV-2026-002');

        $this->postJson('/api/v1/quotes', $this->payload(['issue_date' => '2027-01-05', 'validity_date' => null]))
            ->assertStatus(201)
            ->assertJsonPath('data.quote_number', 'DEV-2027-001')
            ->assertJsonPath('data.validity_date', '2027-04-05');
    }

    public function test_should_link_quote_to_a_worksite_of_the_client(): void
    {
        $worksite = WorksiteFactory::new()->createOne(['client_id' => $this->client->id]);

        $this->postJson('/api/v1/quotes', $this->payload(['worksite_id' => $worksite->id]))
            ->assertStatus(201)
            ->assertJsonPath('data.worksites.0.id', $worksite->id);
    }

    public function test_should_reject_a_worksite_of_another_client(): void
    {
        $worksite = WorksiteFactory::new()->createOne();

        $this->postJson('/api/v1/quotes', $this->payload(['worksite_id' => $worksite->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('worksite_id');
    }

    public function test_should_require_at_least_one_valid_line(): void
    {
        $this->postJson('/api/v1/quotes', $this->payload(['lines' => []]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('lines');

        $this->postJson('/api/v1/quotes', $this->payload(['lines' => [
            ['description' => '', 'quantity' => 0, 'unit' => 'unknown', 'unit_price_ht' => -1],
        ]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'lines.0.description',
                'lines.0.quantity',
                'lines.0.unit',
                'lines.0.unit_price_ht',
            ]);
    }

    public function test_should_require_a_validity_of_at_least_three_months(): void
    {
        $this->postJson('/api/v1/quotes', $this->payload(['validity_date' => '2026-12-01']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('validity_date');
    }

    public function test_should_update_quote_and_replace_lines(): void
    {
        $quoteId = $this->postJson('/api/v1/quotes', $this->payload())->json('data.id');

        $this->putJson("/api/v1/quotes/{$quoteId}", $this->payload([
            'tva_rate' => TvaRate::Taux10->value,
            'quote_status' => QuoteStatus::Send->value,
            'lines' => [
                ['category' => 'Forfait', 'description' => 'Étude', 'quantity' => 1, 'unit' => Unit::FlatRate->value, 'unit_price_ht' => 4800],
            ],
        ]))
            ->assertOk()
            ->assertJsonPath('data.quote_number', 'DEV-2026-001')
            ->assertJsonPath('data.subtotal_ht.amount', '480000')
            ->assertJsonPath('data.tva_amount.amount', '48000')
            ->assertJsonPath('data.quote_status', QuoteStatus::Send->value)
            ->assertJsonCount(1, 'data.product_lines');

        $this->assertDatabaseCount('product_lines', 1);
    }

    public function test_should_update_quote_status(): void
    {
        $quote = QuoteFactory::new()->createOne(['quote_status' => QuoteStatus::Send]);

        $this->patchJson("/api/v1/quotes/{$quote->id}/status", ['quote_status' => QuoteStatus::Accept->value])
            ->assertOk()
            ->assertJsonPath('data.quote_status', QuoteStatus::Accept->value);
    }

    public function test_should_soft_delete_quote(): void
    {
        $quote = QuoteFactory::new()->createOne();

        $this->deleteJson("/api/v1/quotes/{$quote->id}")->assertNoContent();

        $this->assertSoftDeleted('quotes', ['id' => $quote->id]);
        $this->getJson("/api/v1/quotes/{$quote->id}")->assertNotFound();
    }

    public function test_should_search_quotes_by_number_or_client(): void
    {
        QuoteFactory::new()->createOne(['client_id' => $this->client->id, 'quote_number' => 'DEV-2026-042']);
        QuoteFactory::new()->createOne(['quote_number' => 'DEV-2026-043']);

        $this->getJson('/api/v1/quotes?search=limoges')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.quote_number', 'DEV-2026-042');

        $this->getJson('/api/v1/quotes?search=043')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_should_filter_quotes_by_client(): void
    {
        QuoteFactory::new()->count(2)->create(['client_id' => $this->client->id]);
        QuoteFactory::new()->createOne();

        $this->getJson("/api/v1/quotes?client_id={$this->client->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_should_show_quote_with_client_address(): void
    {
        $quoteId = $this->postJson('/api/v1/quotes', $this->payload())->json('data.id');

        $this->getJson("/api/v1/quotes/{$quoteId}")
            ->assertOk()
            ->assertJsonPath('data.client.city', $this->client->city)
            ->assertJsonPath('data.tva_rate_numeric', 20)
            ->assertJsonPath('data.product_lines.0.unit_title', 'Mètre carré');
    }

    public function test_should_return_404_for_unknown_quote(): void
    {
        $this->putJson('/api/v1/quotes/999999', $this->payload())->assertNotFound();
        $this->deleteJson('/api/v1/quotes/999999')->assertNotFound();
        $this->assertSame(0, Quote::query()->count());
    }
}
