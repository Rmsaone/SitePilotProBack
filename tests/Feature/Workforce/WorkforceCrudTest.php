<?php

declare(strict_types=1);

namespace Tests\Feature\Workforce;

use App\Models\Worksite;
use Database\Factories\WorkforceFactory;
use Database\Factories\WorksiteFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkforceCrudTest extends TestCase
{
    use RefreshDatabase;

    private Worksite $worksite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUser();
        $this->worksite = WorksiteFactory::new()->createOne();
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'worksite_id' => $this->worksite->id,
            'code' => '56',
            'worker' => 'MUNOZ Michel',
            'hr_working' => 13.5,
            'hr_rate' => 32.01,
            'additional_costs' => 10,
        ], $overrides);
    }

    public function test_should_create_worker_and_compute_costs(): void
    {
        $this->postJson('/api/v1/workforces', $this->payload())
            ->assertStatus(201)
            ->assertJsonPath('data.hr_working', 13.5)
            ->assertJsonPath('data.cost_hr_working.amount', '43214')
            ->assertJsonPath('data.total_gross_cost.amount', '44214');

        $this->assertDatabaseHas('workforces', [
            'worker' => 'MUNOZ Michel',
            'cost_hr_working' => 43214,
            'total_gross_cost' => 44214,
        ]);
    }

    public function test_should_validate_worker_payload(): void
    {
        $this->postJson('/api/v1/workforces', $this->payload(['worker' => '', 'hr_working' => -2]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['worker', 'hr_working']);
    }

    public function test_should_update_worker(): void
    {
        $workforce = WorkforceFactory::new()->createOne(['worksite_id' => $this->worksite->id]);

        $this->putJson("/api/v1/workforces/{$workforce->id}", $this->payload(['hr_working' => 10, 'additional_costs' => null]))
            ->assertOk()
            ->assertJsonPath('data.total_gross_cost.amount', '32010');
    }

    public function test_should_delete_worker(): void
    {
        $workforce = WorkforceFactory::new()->createOne(['worksite_id' => $this->worksite->id]);

        $this->deleteJson("/api/v1/workforces/{$workforce->id}")->assertNoContent();

        $this->assertSoftDeleted('workforces', ['id' => $workforce->id]);
    }

    public function test_should_filter_workers_by_worksite(): void
    {
        WorkforceFactory::new()->count(3)->create(['worksite_id' => $this->worksite->id]);

        $this->getJson("/api/v1/workforces?worksite_id={$this->worksite->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }
}
