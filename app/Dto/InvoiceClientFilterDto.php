<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enums\PaymentStatus;

final readonly class InvoiceClientFilterDto
{
    public function __construct(
        public ?string $search = null,
        public ?PaymentStatus $paymentStatus = null,
        public ?int $clientId = null,
        public ?int $worksiteId = null,
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
            paymentStatus: isset($data['payment_status']) ? PaymentStatus::from((string) $data['payment_status']) : null,
            clientId: isset($data['client_id']) ? (int) $data['client_id'] : null,
            worksiteId: isset($data['worksite_id']) ? (int) $data['worksite_id'] : null,
            perPage: (int) ($data['per_page'] ?? 20),
        );
    }
}
