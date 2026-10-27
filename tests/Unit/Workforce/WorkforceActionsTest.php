<?php

declare(strict_types=1);

namespace Tests\Unit\Workforce;

use App\Actions\Workforce\CreateWorkforceAction;
use App\Actions\Workforce\UpdateWorkforceAction;
use App\Actions\Workforce\WorkforceCosts;
use App\Dto\WorkforceDto;
use App\Models\Worksite;
use Database\Factories\WorkforceFactory;
use Database\Factories\WorksiteFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * @internal
 */
#[CoversClass(CreateWorkforceAction::class)]
#[CoversClass(UpdateWorkforceAction::class)]
#[CoversClass(WorkforceCosts::class)]
final class WorkforceActionsTest extends TestCase
{
    use DatabaseTransactions;

    private Worksite $worksite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->worksite = WorksiteFactory::new()->createOne();
    }

    private function makeDto(array $overrides = []): WorkforceDto
    {
        return WorkforceDto::fromArray(array_merge([
            'worksite_id' => $this->worksite->id,
            'code' => '100',
            'worker' => 'NOVO Daniel',
            'hr_working' => 73.5,
            'hr_rate' => 31.78,
            'additional_costs' => null,
        ], $overrides));
    }

    #[TestDox(
        'Given hours and an hourly rate
        When I call CreateWorkforceAction
        Then the labour cost should be hours × rate'
    )]
    public function test_should_compute_labour_cost(): void
    {
        $workforce = (new CreateWorkforceAction())($this->makeDto());

        $this->assertSame('233583', $workforce->cost_hr_working->getAmount());
        $this->assertSame('233583', $workforce->total_gross_cost->getAmount());
    }

    #[TestDox(
        'Given additional costs
        When I call UpdateWorkforceAction
        Then the gross cost should include them'
    )]
    public function test_should_add_additional_costs_to_gross_cost(): void
    {
        $workforce = WorkforceFactory::new()->createOne(['worksite_id' => $this->worksite->id]);

        $result = (new UpdateWorkforceAction())($workforce, $this->makeDto([
            'hr_working' => 10,
            'hr_rate' => 30,
            'additional_costs' => 50,
        ]));

        $this->assertSame('30000', $result->cost_hr_working->getAmount());
        $this->assertSame('35000', $result->total_gross_cost->getAmount());
        $this->assertSame(10.0, $result->hr_working);
    }
}
