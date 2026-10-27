<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enums\WorksitePriority;
use App\Enums\WorksiteStatus;
use ArrayAccess;
use Illuminate\Support\Carbon;

final readonly class WorksiteDto
{
    public function __construct(
        public int $client_id,
        public ?string $code,
        public string $nameWorksite,
        public ?string $description,
        public ?Carbon $startDate,
        public ?Carbon $endDate,
        public WorksitePriority $worksitePriority,
        public WorksiteStatus $worksiteStatus,
        public string $street,
        public string $city,
        public int $zip_code,
        public string $country,
        public ?int $quoteId = null,
        public ?int $userId = null,
    ) {
    }

    /**
     * @param  array<string,scalar|null>|ArrayAccess  $data
     */
    public static function fromArray(array|ArrayAccess $data): self
    {
        return new self(
            client_id: (int) $data['client_id'],
            code: $data['code'] ?? null,
            nameWorksite: (string) $data['name_worksite'],
            description: $data['description'] ?? null,
            startDate: isset($data['start_date'])
                ? Carbon::parse($data['start_date'])
                : null,
            endDate: isset($data['end_date'])
                ? Carbon::parse($data['end_date'])
                : null,
            worksitePriority: WorksitePriority::from($data['worksite_priority']),
            worksiteStatus: WorksiteStatus::from($data['worksite_status']),
            street: (string) $data['street'],
            city: (string) $data['city'],
            zip_code: (int) $data['zip_code'],
            country: (string) $data['country'],
            quoteId: isset($data['quote_id']) ? (int) $data['quote_id'] : null,
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
        );
    }
}
