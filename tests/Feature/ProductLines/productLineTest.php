<?php

declare(strict_types=1);

namespace Feature\ProductLines;

use App\Models\ProductLine;
use Database\Factories\ProductLineFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class productLineTest extends TestCase
{
    use RefreshDatabase;

    private ProductLine $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUser();

        $this->invoice = ProductLineFactory::new()
            ->createOne();
    }

    public function test_invoice_subcontractor_index_route_exists(): void
    {
        $response = $this->getJson('/api/v1/product_lines');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_should_return_empty_list(): void
    {
        ProductLine::query()->delete();

        $response = $this->getJson('/api/v1/product_lines');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }
}
