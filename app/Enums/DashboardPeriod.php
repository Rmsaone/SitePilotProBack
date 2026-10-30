<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Carbon;

enum DashboardPeriod: string
{
    case Month = 'month';
    case Quarter = 'quarter';
    case Year = 'year';
    case All = 'all';

    public function title(): string
    {
        return match ($this) {
            self::Month => 'Ce mois',
            self::Quarter => 'Ce trimestre',
            self::Year => 'Cette année',
            self::All => 'Tout',
        };
    }

    /**
     * Current period, null for "all".
     *
     * @return array{Carbon,Carbon}|null
     */
    public function range(Carbon $now): ?array
    {
        return match ($this) {
            self::Month => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            self::Quarter => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            self::Year => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            self::All => null,
        };
    }

    /**
     * Same-length period just before, used for the trends.
     *
     * @return array{Carbon,Carbon}|null
     */
    public function previousRange(Carbon $now): ?array
    {
        return match ($this) {
            self::Month => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            self::Quarter => [$now->copy()->subQuarterNoOverflow()->startOfQuarter(), $now->copy()->subQuarterNoOverflow()->endOfQuarter()],
            self::Year => [$now->copy()->subYearNoOverflow()->startOfYear(), $now->copy()->subYearNoOverflow()->endOfYear()],
            self::All => null,
        };
    }
}
