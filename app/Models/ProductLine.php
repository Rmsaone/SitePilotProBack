<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TvaRate;
use App\Enums\Unit;
use Cknow\Money\Casts\MoneyIntegerCast;
use Cknow\Money\Money;
use Database\Factories\ProductLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * - Attributes.
 *
 * @property int $id
 * @property int $quote_id
 * @property string $description
 * @property float $quantity
 * @property Unit $unit
 * @property Money $unit_price_ht
 * @property TvaRate $tva_rate
 * @property Money $total_ht
 * @property Money $total_ttc
 * @property int $sort_order
 * @property string|null $category
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * - Relations.
 * @property Quote $quote
 */
class ProductLine extends Model
{
    /** @use HasFactory<ProductLineFactory> */
    use HasFactory;

    /**
     * @var array<int,string>
     */
    protected $guarded = ['id'];

    #[Override]
    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit' => Unit::class,
            'unit_price_ht' => MoneyIntegerCast::class.':EUR',
            'tva_rate' => TvaRate::class,
            'total_ht' => MoneyIntegerCast::class.':EUR',
            'total_ttc' => MoneyIntegerCast::class.':EUR',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
