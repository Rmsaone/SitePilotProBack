<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enums\ClientType;

final readonly class ClientFilterDto
{
    public function __construct(
        public ?string $search = null,
        public ?ClientType $clientType = null,
        public string $sort = 'created_at',
        public string $direction = 'desc',
        public int $perPage = 20,
    ) {
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            search: isset($data['search']) ? (string) $data['search'] : null,
            clientType: isset($data['client_type']) ? ClientType::from((string) $data['client_type']) : null,
            sort: (string) ($data['sort'] ?? 'created_at'),
            direction: (string) ($data['direction'] ?? 'desc'),
            perPage: (int) ($data['per_page'] ?? 20),
        );
    }
}
