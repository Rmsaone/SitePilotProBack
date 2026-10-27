<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Enums\TvaRate;
use Cknow\Money\Casts\MoneyIntegerCast;
use Cknow\Money\Money;
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Override;

/**
 * - Attributes.
 *
 * @property int $id
 * @property int $client_id
 * @property string $quote_number
 * @property Carbon $issue_date
 * @property Carbon $validity_date
 * @property QuoteStatus $quote_status
 * @property Money $subtotal_ht
 * @property Money $total_ttc
 * @property TvaRate $tva_rate
 * @property Money $tva_amount
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * - Relations.
 * @property Client $client
 * @property Collection<int,ProductLine> $productLines
 * @property Collection<int,Worksite> $worksites
 * @property Collection<int,InvoiceClient> $invoicesClients
 */
class Quote extends Model
{
    /** @use HasFactory<QuoteFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var array<int,string>
     */
    protected $guarded = ['id'];

    #[Override]
    protected function casts(): array
    {
        return [
            'quote_number' => 'string',
            'issue_date' => 'datetime',
            'validity_date' => 'datetime',
            'quote_status' => QuoteStatus::class,
            'tva_rate' => TvaRate::class,
            'subtotal_ht' => MoneyIntegerCast::class.':EUR',
            'total_ttc' => MoneyIntegerCast::class.':EUR',
            'tva_amount' => MoneyIntegerCast::class.':EUR',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function productLines(): HasMany
    {
        return $this->hasMany(ProductLine::class);
    }

    public function worksites(): BelongsToMany
    {
        return $this->belongsToMany(Worksite::class, 'worksite_quote')
            ->withTimestamps();
    }

    public function invoicesClients(): HasMany
    {
        return $this->hasMany(InvoiceClient::class);
    }
}
