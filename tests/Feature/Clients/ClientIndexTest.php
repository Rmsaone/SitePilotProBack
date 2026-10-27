<?php

declare(strict_types=1);

namespace Tests\Feature\Clients;

use Database\Factories\ClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUser();
    }

    public function test_should_search_clients(): void
    {
        ClientFactory::new()->createOne(['company' => 'SCI Les Acacias']);
        ClientFactory::new()->createOne(['company' => 'Ville de Pau']);

        $this->getJson('/api/v1/clients?search=acacias')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.company', 'SCI Les Acacias');
    }

    public function test_should_paginate_clients_with_per_page(): void
    {
        ClientFactory::new()->count(30)->create();

        $this->getJson('/api/v1/clients?per_page=25')
            ->assertOk()
            ->assertJsonCount(25, 'data');
    }
}
