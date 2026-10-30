<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enums\WorksiteStatus;

final readonly class WorksiteFilterDto
{
    public function __construct(
        public ?string $search,
        public ?WorksiteStatus $worksiteStatus,
        public string $sort,
        public string $direction,
        public int $perPage,
        public ?int $clientId = null,
    ) {
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            search: isset($data['search']) ? (string) $data['search'] : null,
            worksiteStatus: isset($data['worksite_status'])
                ? WorksiteStatus::from($data['worksite_status'])
                : null,
            sort: (string) ($data['sort'] ?? 'created_at'),
            direction: (string) ($data['direction'] ?? 'desc'),
            perPage: (int) ($data['per_page'] ?? 20),
            clientId: isset($data['client_id']) ? (int) $data['client_id'] : null,
        );
    }
}
