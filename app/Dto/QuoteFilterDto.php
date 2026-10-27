<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enums\QuoteStatus;

final readonly class QuoteFilterDto
{
    public function __construct(
        public ?string $search = null,
        public ?QuoteStatus $quoteStatus = null,
        public ?int $clientId = null,
        public ?int $worksiteId = null,
        public int $perPage = 15,
    ) {
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            search: isset($data['search']) ? (string) $data['search'] : null,
            quoteStatus: isset($data['quote_status']) ? QuoteStatus::from((string) $data['quote_status']) : null,
            clientId: isset($data['client_id']) ? (int) $data['client_id'] : null,
            worksiteId: isset($data['worksite_id']) ? (int) $data['worksite_id'] : null,
            perPage: (int) ($data['per_page'] ?? 15),
        );
    }
}
