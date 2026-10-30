<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Support\Carbon;

final readonly class InvoicePaymentDto
{
    public function __construct(
        public PaymentStatus $paymentStatus,
        public ?Carbon $paymentDate,
        public ?PaymentMethod $paymentMethod,
    ) {
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            paymentStatus: PaymentStatus::from((string) $data['payment_status']),
            paymentDate: isset($data['payment_date']) ? Carbon::parse($data['payment_date']) : null,
            paymentMethod: isset($data['payment_method']) ? PaymentMethod::from((string) $data['payment_method']) : null,
        );
    }
}
