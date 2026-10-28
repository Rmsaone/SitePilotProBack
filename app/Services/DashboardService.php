<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DashboardPeriod;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Enums\WorksiteStatus;
use App\Models\InvoiceClient;
use App\Models\InvoiceOther;
use App\Models\InvoiceSubcontractor;
use App\Models\InvoiceSupplier;
use App\Models\Quote;
use App\Models\Worksite;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Figures of the dashboard: counters with their trend, revenue, amount left to invoice
 * and the monthly invoiced / spent amounts.
 */
class DashboardService
{
    private const int CHART_MONTHS = 12;

    /** Worksites still running: their contract may not be fully invoiced yet. */
    private const array ACTIVE_STATUSES = [WorksiteStatus::Pending, WorksiteStatus::InProgress, WorksiteStatus::Paused];

    /**
     * @return array<string,mixed>
     */
    public function summarize(DashboardPeriod $period, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();
        $current = $period->range($now);
        $previous = $period->previousRange($now);

        $acceptedQuotes = fn (?array $range): int => $this->inRange(
            Quote::query()->where('quote_status', QuoteStatus::Accept),
            'issue_date',
            $range
        )->count();
        $createdQuotes = fn (?array $range): int => $this->inRange(Quote::query(), 'issue_date', $range)->count();
        $invoices = fn (?array $range): int => $this->inRange(InvoiceClient::query(), 'delivery_date', $range)->count();
        $revenue = fn (?array $range): int => (int) $this->inRange(
            InvoiceClient::query()->where('payment_status', '!=', PaymentStatus::Canceled),
            'delivery_date',
            $range
        )->sum('total_ht');

        [$remaining, $remainingWorksites] = $this->remainingToInvoice();

        return [
            'period' => $period->value,
            'period_title' => $period->title(),
            'worksites_in_progress' => [
                'value' => Worksite::query()->where('worksite_status', WorksiteStatus::InProgress)->count(),
                'trend' => null,
            ],
            'accepted_quotes' => $this->counter($acceptedQuotes, $current, $previous),
            'invoices' => $this->counter($invoices, $current, $previous),
            'created_quotes' => $this->counter($createdQuotes, $current, $previous),
            'revenue' => [
                'amount' => Money::EUR($revenue($current)),
                'trend' => $previous === null ? null : $this->trend($revenue($current), $revenue($previous)),
            ],
            'remaining_to_invoice' => [
                'amount' => Money::EUR($remaining),
                'worksites' => $remainingWorksites,
            ],
            'monthly' => $this->monthly($now),
        ];
    }

    /**
     * @param  callable(array{Carbon,Carbon}|null):int  $count
     * @param  array{Carbon,Carbon}|null  $current
     * @param  array{Carbon,Carbon}|null  $previous
     * @return array{value:int,trend:float|null}
     */
    private function counter(callable $count, ?array $current, ?array $previous): array
    {
        $value = $count($current);

        return [
            'value' => $value,
            'trend' => $previous === null ? null : $this->trend($value, $count($previous)),
        ];
    }

    /**
     * Variation in percent, null when there is nothing to compare with.
     */
    private function trend(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    /**
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $query
     * @param  array{Carbon,Carbon}|null  $range
     * @return TBuilder
     */
    private function inRange(Builder $query, string $column, ?array $range): Builder
    {
        if ($range !== null) {
            $query->whereBetween($column, $range);
        }

        return $query;
    }

    /**
     * Sum over active worksites of (accepted quotes - invoiced), never negative.
     *
     * @return array{int,int} amount in cents and number of worksites concerned
     */
    private function remainingToInvoice(): array
    {
        $activeIds = Worksite::query()
            ->whereIn('worksite_status', self::ACTIVE_STATUSES)
            ->pluck('id');

        /** @var array<int,int> $market */
        $market = DB::table('worksite_quote')
            ->join('quotes', 'quotes.id', '=', 'worksite_quote.quote_id')
            ->whereIn('worksite_quote.worksite_id', $activeIds)
            ->where('quotes.quote_status', QuoteStatus::Accept->value)
            ->whereNull('quotes.deleted_at')
            ->groupBy('worksite_quote.worksite_id')
            ->selectRaw('worksite_quote.worksite_id, SUM(quotes.subtotal_ht) AS total')
            ->pluck('total', 'worksite_id')
            ->map(fn (mixed $sum): int => (int) $sum)
            ->all();

        /** @var array<int,int> $invoiced */
        $invoiced = InvoiceClient::query()
            ->whereIn('worksite_id', array_keys($market))
            ->where('payment_status', '!=', PaymentStatus::Canceled)
            ->groupBy('worksite_id')
            ->selectRaw('worksite_id, SUM(total_ht) AS total')
            ->pluck('total', 'worksite_id')
            ->map(fn (mixed $sum): int => (int) $sum)
            ->all();

        $amount = 0;
        $worksites = 0;

        foreach ($market as $worksiteId => $marketAmount) {
            $left = $marketAmount - ($invoiced[$worksiteId] ?? 0);

            if ($left > 0) {
                $amount += $left;
                $worksites++;
            }
        }

        return [$amount, $worksites];
    }

    /**
     * Invoiced (client invoices) vs spent (purchase invoices at cost price), month by month.
     *
     * @return list<array{month:string,invoiced:Money,spent:Money}>
     */
    private function monthly(Carbon $now): array
    {
        $start = $now->copy()->startOfMonth()->subMonths(self::CHART_MONTHS - 1);
        $end = $now->copy()->endOfMonth();

        /** @var Collection<int,InvoiceClient> $clientInvoices */
        $clientInvoices = InvoiceClient::query()
            ->where('payment_status', '!=', PaymentStatus::Canceled)
            ->whereBetween('delivery_date', [$start, $end])
            ->get(['delivery_date', 'total_ht']);

        $invoiced = $this->sumByMonth($clientInvoices, 'total_ht');

        $spent = [];

        foreach ([InvoiceSupplier::query(), InvoiceSubcontractor::query(), InvoiceOther::query()] as $query) {
            /** @var Collection<int,InvoiceOther|InvoiceSubcontractor|InvoiceSupplier> $purchases */
            $purchases = $query->whereBetween('delivery_date', [$start, $end])->get(['delivery_date', 'cost_price']);

            foreach ($this->sumByMonth($purchases, 'cost_price') as $month => $sum) {
                $spent[$month] = ($spent[$month] ?? 0) + $sum;
            }
        }

        $months = [];

        for ($i = 0; $i < self::CHART_MONTHS; $i++) {
            $key = $start->copy()->addMonths($i)->format('Y-m');
            $months[] = [
                'month' => $key,
                'invoiced' => Money::EUR($invoiced[$key] ?? 0),
                'spent' => Money::EUR($spent[$key] ?? 0),
            ];
        }

        return $months;
    }

    /**
     * @param  iterable<InvoiceClient|InvoiceOther|InvoiceSubcontractor|InvoiceSupplier>  $invoices
     * @return array<string,int>
     */
    private function sumByMonth(iterable $invoices, string $attribute): array
    {
        $byMonth = [];

        foreach ($invoices as $invoice) {
            /** @var Money $amount */
            $amount = $invoice->{$attribute};
            $key = $invoice->delivery_date->format('Y-m');
            $byMonth[$key] = ($byMonth[$key] ?? 0) + (int) $amount->getAmount();
        }

        return $byMonth;
    }
}
