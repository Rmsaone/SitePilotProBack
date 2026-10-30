<?php

declare(strict_types=1);

namespace Feature\Clients;

use App\Models\Client;
use Database\Factories\ClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUser();

        $this->client = ClientFactory::new()->createOne();
    }

    public function test_clients_index_route_exists(): void
    {
        $response = $this->getJson('/api/v1/clients');
        $response->assertStatus(200);
    }
}
