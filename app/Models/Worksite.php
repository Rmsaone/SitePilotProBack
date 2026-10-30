<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WorksitePriority;
use App\Enums\WorksiteStatus;
use Database\Factories\WorksiteFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * - Attributes.
 *
 * @property int $id
 * @property int $client_id
 * @property string|null $code
 * @property string $name_worksite
 * @property string|null $description
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property WorksitePriority $worksite_priority
 * @property WorksiteStatus $worksite_status
 * @property string $street
 * @property string $city
 * @property int $zip_code
 * @property string $country
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * - Relations.
 * @property Client $client
 * @property Collection<int,User> $users
 * @property Collection<int,Quote> $quotes
 * @property Collection<int,InvoiceClient> $invoiceClients
 * @property Collection<int,InvoiceSupplier> $invoiceSuppliers
 * @property Collection<int,InvoiceSubcontractor> $invoiceSubcontractors
 * @property Collection<int,InvoiceOther> $invoiceOthers
 * @property Collection<int,Workforce> $workforce
 */
class Worksite extends Model
{
    /** @use HasFactory<WorksiteFactory> */
    use HasFactory;

    /**
     * @var array<int,string>
     */
    protected $guarded = ['id'];

    #[Override]
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'worksite_priority' => WorksitePriority::class,
            'worksite_status' => WorksiteStatus::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'worksite_user')
            ->withTimestamps();
    }

    public function quotes(): BelongsToMany
    {
        return $this->belongsToMany(Quote::class, 'worksite_quote')
            ->withTimestamps();
    }

    public function invoiceClients(): HasMany
    {
        return $this->hasMany(InvoiceClient::class);
    }

    public function invoiceSuppliers(): HasMany
    {
        return $this->hasMany(InvoiceSupplier::class);
    }

    public function invoiceSubcontractors(): HasMany
    {
        return $this->hasMany(InvoiceSubcontractor::class);
    }

    public function invoiceOthers(): HasMany
    {
        return $this->hasMany(InvoiceOther::class);
    }

    public function workforce(): HasMany
    {
        return $this->hasMany(Workforce::class);
    }
}
