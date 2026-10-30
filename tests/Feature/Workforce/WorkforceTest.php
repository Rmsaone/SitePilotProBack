<?php

declare(strict_types=1);

namespace Feature\Workforce;

use App\Models\Workforce;
use Database\Factories\WorkforceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkforceTest extends TestCase
{
    use RefreshDatabase;

    private Workforce $workforce;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUser();

        $this->workforce = WorkforceFactory::new()->createOne();
    }

    public function test_workforces_index_route_exists(): void
    {
        $response = $this->getJson('/api/v1/workforces');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_should_return_correct_data(): void
    {
        $response = $this->getJson('/api/v1/workforces');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'worker' => $this->workforce->worker,
        ]);
    }

    public function test_should_return_empty_list(): void
    {
        Workforce::query()->delete();

        $response = $this->getJson('/api/v1/workforces');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }
}
