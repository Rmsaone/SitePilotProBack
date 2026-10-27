<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enums\Unit;
use Cknow\Money\Money;

final readonly class ProductLineDto
{
    public function __construct(
        public ?string $category,
        public string $description,
        public float $quantity,
        public Unit $unit,
        public Money $unitPriceHt,
    ) {
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $category = isset($data['category']) ? trim((string) $data['category']) : '';

        return new self(
            category: $category !== '' ? $category : null,
            description: (string) $data['description'],
            quantity: (float) $data['quantity'],
            unit: Unit::from((string) $data['unit']),
            unitPriceHt: Money::EUR((int) round((float) $data['unit_price_ht'] * 100)),
        );
    }

    public function totalHt(): Money
    {
        return $this->unitPriceHt->multiply((string) $this->quantity);
    }
}
