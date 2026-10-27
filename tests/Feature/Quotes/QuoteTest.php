<?php

declare(strict_types=1);

namespace Feature\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Database\Factories\QuoteFactory;
use Database\Factories\WorksiteFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use RefreshDatabase;

    private Quote $quote;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUser();

        $this->quote = QuoteFactory::new()->createOne();
    }

    public function test_quotes_index_route_exists(): void
    {
        $response = $this->getJson('/api/v1/quotes');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_should_return_correct_data(): void
    {
        $response = $this->getJson('/api/v1/quotes');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'quote_number' => $this->quote->quote_number,
        ]);
    }

    public function test_should_return_empty_list(): void
    {
        Quote::query()->delete();

        $response = $this->getJson('/api/v1/quotes');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    public function test_should_filter_quotes_by_status(): void
    {
        Quote::query()->delete();
        QuoteFactory::new()->count(2)->create(['quote_status' => QuoteStatus::Accept]);
        QuoteFactory::new()->createOne(['quote_status' => QuoteStatus::Draft]);

        $this->getJson('/api/v1/quotes?quote_status=accept')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_should_filter_quotes_by_worksite(): void
    {
        $worksite = WorksiteFactory::new()->createOne();
        $worksite->quotes()->attach($this->quote->id);
        QuoteFactory::new()->createOne();

        $this->getJson("/api/v1/quotes?worksite_id={$worksite->id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->quote->id);
    }
}
