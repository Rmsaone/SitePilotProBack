<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\Dto\ProductLineDto;
use App\Enums\TvaRate;
use App\Models\Quote;
use Cknow\Money\Money;

/**
 * Replaces the product lines of a quote and recomputes its totals.
 */
final class SyncQuoteLinesAction
{
    /**
     * @param  list<ProductLineDto>  $lines
     */
    public function __invoke(
        Quote $quote,
        array $lines,
        TvaRate $tvaRate,
    ): Quote {
        $quote->productLines()->delete();

        $subtotal = Money::EUR(0);

        foreach ($lines as $index => $line) {
            $totalHt = $line->totalHt();
            $subtotal = $subtotal->add($totalHt);

            $quote->productLines()->create([
                'category' => $line->category,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit' => $line->unit,
                'unit_price_ht' => $line->unitPriceHt,
                'tva_rate' => $tvaRate,
                'total_ht' => $totalHt,
                'total_ttc' => $totalHt->add(self::tva($totalHt, $tvaRate)),
                'sort_order' => $index + 1,
            ]);
        }

        $tvaAmount = self::tva($subtotal, $tvaRate);

        $quote->update([
            'tva_rate' => $tvaRate,
            'subtotal_ht' => $subtotal,
            'tva_amount' => $tvaAmount,
            'total_ttc' => $subtotal->add($tvaAmount),
        ]);

        return $quote;
    }

    public static function tva(Money $amount, TvaRate $tvaRate): Money
    {
        return $amount->multiply((string) ($tvaRate->numeric() / 100));
    }
}
