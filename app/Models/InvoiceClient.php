<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TvaRate;
use Cknow\Money\Casts\MoneyIntegerCast;
use Cknow\Money\Money;
use Database\Factories\InvoiceClientFactory;
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
 * @property int $client_id
 * @property int $worksite_id
 * @property int|null $quote_id
 * @property Carbon $delivery_date
 * @property string $number_invoice
 * @property string $invoice_description
 * @property Money $total_ht
 * @property TvaRate $tva_rate
 * @property Carbon|null $payment_date
 * @property PaymentStatus $payment_status
 * @property PaymentMethod|null $payment_method
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * - Relations.
 * @property Worksite $worksite
 * @property Quote|null $quote
 * @property Client $client
 */
class InvoiceClient extends Model
{
    /** @use HasFactory<InvoiceClientFactory> */
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
            'total_ht' => MoneyIntegerCast::class.':EUR',
            'tva_rate' => TvaRate::class,
            'payment_date' => 'datetime',
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function worksite(): BelongsTo
    {
        return $this->belongsTo(Worksite::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
