<?php

declare(strict_types=1);

namespace Feature\invoiceSubcontractor;

use App\Models\InvoiceSubcontractor;
use Database\Factories\InvoiceSubcontractorFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceSubcontractorTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceSubcontractor $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUser();

        $this->invoice = InvoiceSubcontractorFactory::new()
            ->createOne();
    }

    public function test_invoice_subcontractor_index_route_exists(): void
    {
        $response = $this->getJson('/api/v1/invoice_subcontractors');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_should_return_correct_data(): void
    {
        $response = $this->getJson('/api/v1/invoice_subcontractors');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'number_invoice' => $this->invoice->number_invoice,
        ]);
    }

    public function test_should_return_empty_list(): void
    {
        InvoiceSubcontractor::query()->delete();

        $response = $this->getJson('/api/v1/invoice_subcontractors');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }
}
