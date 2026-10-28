<?php

declare(strict_types=1);

namespace App\Models;

use Cknow\Money\Casts\MoneyIntegerCast;
use Cknow\Money\Money;
use Database\Factories\InvoiceSupplierFactory;
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
 * @property Carbon $delivery_date
 * @property string $number_invoice
 * @property string $invoice_description
 * @property Money $purchase_price
 * @property Money $cost_price
 * @property string|null $attachment_path
 * @property string|null $attachment_name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * - Relations.
 * @property Worksite $worksite
 */
class InvoiceSupplier extends Model
{
    /** @use HasFactory<InvoiceSupplierFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var array<int,string>
     */
    protected $guarded = ['id'];

    #[Override]
    protected function casts(): array
    {
        return [
            'delivery_date' => 'datetime',
            'purchase_price' => MoneyIntegerCast::class.':EUR',
            'cost_price' => MoneyIntegerCast::class.':EUR',
        ];
    }

    public function worksite(): BelongsTo
    {
        return $this->belongsTo(Worksite::class);
    }
}
