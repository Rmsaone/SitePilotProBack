<?php

declare(strict_types=1);

namespace Tests\Feature\Worksites;

use App\Models\Client;
use App\Models\Worksite;
use Database\Factories\ClientFactory;
use Database\Factories\WorksiteFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorksiteTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    private Worksite $worksite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUser();

        $this->client = ClientFactory::new()->createOne();
        $this->worksite = WorksiteFactory::new()
            ->createOne();
    }

    public function test_worksites_index_route_exists(): void
    {
        $response = $this->getJson('/api/v1/worksites');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_should_return_paginated_worksites(): void
    {
        WorksiteFactory::new()
            ->createMany(9);

        $response = $this->getJson('/api/v1/worksites?page=1');

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
    }

    public function test_should_when_user_access_other_client_worksite(): void
    {
        $worksite = WorksiteFactory::new()
            ->createOne();

        $response = $this->getJson("/api/v1/worksites/{$worksite->id}");

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'name_worksite',
                'city',
                'zip_code',
            ],
        ]);

        $response->assertJsonPath('data.id', $worksite->id);
        $response->assertJsonPath('data.name_worksite', $worksite->name_worksite);
    }

    public function test_should_return_correct_data(): void
    {
        $response = $this->getJson('/api/v1/worksites');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name_worksite' => $this->worksite->name_worksite,
        ]);
    }

    public function test_should_return_empty_list(): void
    {
        Worksite::query()->delete();

        $response = $this->getJson('/api/v1/worksites');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }
}
