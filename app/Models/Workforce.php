<?php

declare(strict_types=1);

namespace App\Models;

use Cknow\Money\Casts\MoneyIntegerCast;
use Cknow\Money\Money;
use Database\Factories\WorkforceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Override;

/**
 * - Attributes.
 *
 * @property int $id
 * @property int $worksite_id
 * @property string $code
 * @property string $worker
 * @property float $hr_working
 * @property Money $hr_rate
 * @property Money $cost_hr_working
 * @property Money $additional_costs
 * @property Money $total_gross_cost
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * - Relations.
 * @property Worksite $worksite
 */
class Workforce extends Model
{
    /** @use HasFactory<WorkforceFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var array<int,string>
     */
    protected $guarded = ['id'];

    #[Override]
    protected function casts(): array
    {
        return [
            'hr_working' => 'float',
            'hr_rate' => MoneyIntegerCast::class.':EUR',
            'cost_hr_working' => MoneyIntegerCast::class.':EUR',
            'additional_costs' => MoneyIntegerCast::class.':EUR',
            'total_gross_cost' => MoneyIntegerCast::class.':EUR',
        ];
    }

    public function worksite(): BelongsTo
    {
        return $this->belongsTo(Worksite::class);
    }
}
