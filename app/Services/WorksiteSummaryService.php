<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Models\InvoiceClient;
use App\Models\InvoiceOther;
use App\Models\InvoiceSubcontractor;
use App\Models\InvoiceSupplier;
use App\Models\Quote;
use App\Models\Workforce;
use App\Models\Worksite;
use Cknow\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Financial overview of a worksite ("Récapitulatif" tab).
 * Every amount is computed in cents and exposed as Money.
 */
class WorksiteSummaryService
{
    private const int CHART_MONTHS = 12;

    /**
     * @return array<string,mixed>
     */
    public function summarize(Worksite $worksite): array
    {
        $worksite->loadMissing([
            'quotes',
            'invoiceClients',
            'invoiceSuppliers',
            'invoiceSubcontractors',
            'invoiceOthers',
            'workforce',
        ]);

        $acceptedQuotes = $worksite->quotes->filter(fn (Quote $quote): bool => $quote->quote_status === QuoteStatus::Accept);
        $sentQuotes = $worksite->quotes->filter(fn (Quote $quote): bool => $quote->quote_status === QuoteStatus::Send);
        $billedInvoices = $worksite->invoiceClients->reject(fn (InvoiceClient $invoice): bool => $invoice->payment_status === PaymentStatus::Canceled);
        $pendingInvoices = $billedInvoices->filter(fn (InvoiceClient $invoice): bool => in_array(
            $invoice->payment_status,
            [PaymentStatus::Pending, PaymentStatus::Partial, PaymentStatus::Overdue],
            true
        ));

        $market = $this->sum($acceptedQuotes, 'subtotal_ht');
        $invoiced = $this->sum($billedInvoices, 'total_ht');

        $costs = [
            'supplier' => $this->purchaseCosts($worksite->invoiceSuppliers),
            'subcontractor' => $this->purchaseCosts($worksite->invoiceSubcontractors),
            'other' => $this->purchaseCosts($worksite->invoiceOthers),
            'workforce' => [
                'purchase' => $this->sum($worksite->workforce, 'cost_hr_working'),
                'cost' => $this->sum($worksite->workforce, 'total_gross_cost'),
            ],
        ];

        $totalPurchase = array_sum(array_column($costs, 'purchase'));
        $totalCost = array_sum(array_column($costs, 'cost'));

        return [
            'market_amount' => $this->money($market),
            'invoiced_amount' => $this->money($invoiced),
            'remaining_to_invoice' => $this->money(max($market - $invoiced, 0)),
            'remaining_to_spend' => $this->money($market - $totalCost),
            'pending_invoices' => [
                'amount' => $this->money($this->sum($pendingInvoices, 'total_ht')),
                'count' => $pendingInvoices->count(),
            ],
            'pending_quotes' => [
                'amount' => $this->money($this->sum($sentQuotes, 'subtotal_ht')),
                'count' => $sentQuotes->count(),
            ],
            'costs' => array_map(fn (array $cost): array => [
                'purchase' => $this->money($cost['purchase']),
                'cost' => $this->money($cost['cost']),
            ], $costs),
            'total_costs' => [
                'purchase' => $this->money($totalPurchase),
                'cost' => $this->money($totalCost),
            ],
            'gross_margin' => $this->margin($invoiced, $totalPurchase),
            'net_margin' => $this->margin($invoiced, $totalCost),
            'monthly' => $this->monthly($worksite),
        ];
    }

    /**
     * @param  Collection<int,InvoiceOther>|Collection<int,InvoiceSubcontractor>|Collection<int,InvoiceSupplier>  $invoices
     * @return array{purchase:int,cost:int}
     */
    private function purchaseCosts(Collection $invoices): array
    {
        return [
            'purchase' => $this->sum($invoices, 'purchase_price'),
            'cost' => $this->sum($invoices, 'cost_price'),
        ];
    }

    /**
     * @return array{amount:Money,rate:float|null}
     */
    private function margin(int $invoiced, int $costs): array
    {
        $margin = $invoiced - $costs;

        return [
            'amount' => $this->money($margin),
            'rate' => $invoiced > 0 ? round($margin / $invoiced * 100, 1) : null,
        ];
    }

    /**
     * Cumulative invoiced vs spent (cost price) amounts for the last months.
     *
     * @return list<array{month:string,invoiced:Money,spent:Money}>
     */
    private function monthly(Worksite $worksite): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths(self::CHART_MONTHS - 1);

        $invoicedByMonth = $this->groupByMonth(
            $worksite->invoiceClients->reject(fn (InvoiceClient $invoice): bool => $invoice->payment_status === PaymentStatus::Canceled),
            fn (InvoiceClient $invoice): int => $this->cents($invoice->total_ht),
        );
        $spentByMonth = $this->groupByMonth(
            $worksite->invoiceSuppliers
                ->concat($worksite->invoiceSubcontractors)
                ->concat($worksite->invoiceOthers),
            fn (InvoiceSupplier|InvoiceSubcontractor|InvoiceOther $invoice): int => $this->cents($invoice->cost_price),
        );

        $invoiced = $this->sumBefore($invoicedByMonth, $start);
        $spent = $this->sumBefore($spentByMonth, $start);
        $months = [];

        for ($i = 0; $i < self::CHART_MONTHS; $i++) {
            $key = $start->copy()->addMonths($i)->format('Y-m');
            $invoiced += $invoicedByMonth[$key] ?? 0;
            $spent += $spentByMonth[$key] ?? 0;

            $months[] = [
                'month' => $key,
                'invoiced' => $this->money($invoiced),
                'spent' => $this->money($spent),
            ];
        }

        return $months;
    }

    /**
     * @template TModel of InvoiceClient|InvoiceSupplier|InvoiceSubcontractor|InvoiceOther
     *
     * @param  Collection<int,TModel>  $invoices
     * @param  callable(TModel):int  $amount
     * @return array<string,int>
     */
    private function groupByMonth(Collection $invoices, callable $amount): array
    {
        $byMonth = [];

        foreach ($invoices as $invoice) {
            $key = $invoice->delivery_date->format('Y-m');
            $byMonth[$key] = ($byMonth[$key] ?? 0) + $amount($invoice);
        }

        return $byMonth;
    }

    /**
     * @param  array<string,int>  $byMonth
     */
    private function sumBefore(array $byMonth, Carbon $start): int
    {
        $startKey = $start->format('Y-m');

        return array_sum(array_filter(
            $byMonth,
            fn (string $key): bool => $key < $startKey,
            ARRAY_FILTER_USE_KEY
        ));
    }

    /**
     * @param  Collection<int,InvoiceClient|InvoiceOther|InvoiceSubcontractor|InvoiceSupplier|Quote|Workforce>  $models
     */
    private function sum(Collection $models, string $attribute): int
    {
        return (int) $models->sum(fn (Quote|InvoiceClient|InvoiceSupplier|InvoiceSubcontractor|InvoiceOther|Workforce $model): int => $this->cents($model->{$attribute}));
    }

    private function cents(?Money $money): int
    {
        return $money instanceof Money ? (int) $money->getAmount() : 0;
    }

    private function money(int $cents): Money
    {
        return Money::EUR($cents);
    }
}
