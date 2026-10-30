<?php

declare(strict_types=1);

namespace App\Dto;

use Cknow\Money\Money;

final readonly class WorkforceDto
{
    public function __construct(
        public int $worksiteId,
        public string $code,
        public string $worker,
        public float $hrWorking,
        public Money $hrRate,
        public Money $additionalCosts,
    ) {
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            worksiteId: (int) $data['worksite_id'],
            code: (string) $data['code'],
            worker: (string) $data['worker'],
            hrWorking: (float) $data['hr_working'],
            hrRate: Money::EUR((int) round((float) $data['hr_rate'] * 100)),
            additionalCosts: Money::EUR((int) round((float) ($data['additional_costs'] ?? 0) * 100)),
        );
    }
}
