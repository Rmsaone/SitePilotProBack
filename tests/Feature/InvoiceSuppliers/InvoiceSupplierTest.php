<?php

declare(strict_types=1);

namespace Feature\InvoiceSuppliers;

use App\Models\InvoiceSupplier;
use Database\Factories\InvoiceSupplierFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceSupplierTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceSupplier $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUser();

        $this->invoice = InvoiceSupplierFactory::new()
            ->createOne();
    }

    public function test_invoice_supplier_index_route_exists(): void
    {
        $response = $this->getJson('/api/v1/invoice_suppliers');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_should_return_correct_data(): void
    {
        $response = $this->getJson('/api/v1/invoice_suppliers');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'number_invoice' => $this->invoice->number_invoice,
        ]);
    }

    public function test_should_return_empty_list(): void
    {
        InvoiceSupplier::query()->delete();

        $response = $this->getJson('/api/v1/invoice_suppliers');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }
}
