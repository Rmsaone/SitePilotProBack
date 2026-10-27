<?php

declare(strict_types=1);

namespace Feature\InvoiceClients;

use App\Models\InvoiceClient;
use Database\Factories\InvoiceClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceClientTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceClient $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUser();

        $this->invoice = InvoiceClientFactory::new()

            ->createOne();
    }

    public function test_invoice_client_index_route_exists(): void
    {
        $response = $this->getJson('/api/v1/invoice_clients');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_should_return_correct_data(): void
    {
        $response = $this->getJson('/api/v1/invoice_clients');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'number_invoice' => $this->invoice->number_invoice,
        ]);
    }

    public function test_should_return_empty_list(): void
    {
        InvoiceClient::query()->delete();

        $response = $this->getJson('/api/v1/invoice_clients');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }
}
