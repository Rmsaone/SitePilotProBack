<?php

declare(strict_types=1);

namespace Tests\Unit\Worksites;

use App\Models\Worksite;
use Database\Factories\ClientFactory;
use Database\Factories\WorksiteFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class DeleteWorksiteTest extends TestCase
{
    use DatabaseTransactions;

    private Worksite $worksite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUser();

        $client = ClientFactory::new()->createOne();

        $this->worksite = WorksiteFactory::new()
            ->createOne();
    }

    #[TestDox(
        'Given an existing worksite
        When I DELETE /api/v1/worksites/{id}
        Then it should be removed from database'
    )]
    public function test_should_delete_worksite(): void
    {
        $response = $this->deleteJson("/api/v1/worksites/{$this->worksite->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('worksites', [
            'id' => $this->worksite->id,
        ]);
    }

    #[TestDox(
        'Given a non existing worksite
        When I DELETE /api/v1/worksites/{id}
        Then I should get 404'
    )]
    public function test_should_return404_when_deleting_non_existing_worksite(): void
    {
        $fakeId = 999999;

        $response = $this->deleteJson("/api/v1/worksites/{$fakeId}");

        $response->assertStatus(404);
    }

    #[TestDox(
        'Given an existing worksite
        When I DELETE /api/v1/worksites/{id}
        Then the count should decrease by 1'
    )]
    public function test_should_decrement_worksite_count(): void
    {
        $before = Worksite::query()->count();

        $this->deleteJson("/api/v1/worksites/{$this->worksite->id}");

        $this->assertCount($before - 1, Worksite::all());
    }
}
