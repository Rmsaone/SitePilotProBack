<?php

declare(strict_types=1);

namespace Feature\InvoiceOthers;

use App\Models\InvoiceOther;
use Database\Factories\InvoiceOtherFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceOtherTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceOther $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUser();

        $this->invoice = InvoiceOtherFactory::new()
            ->createOne();
    }

    public function test_invoice_other_index_route_exists(): void
    {
        $response = $this->getJson('/api/v1/invoice_others');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_should_return_correct_data(): void
    {
        $response = $this->getJson('/api/v1/invoice_others');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'number_invoice' => $this->invoice->number_invoice,
        ]);
    }

    public function test_should_return_empty_list(): void
    {
        InvoiceOther::query()->delete();

        $response = $this->getJson('/api/v1/invoice_others');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }
}
