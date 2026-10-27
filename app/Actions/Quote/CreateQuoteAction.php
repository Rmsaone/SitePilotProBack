<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\Dto\QuoteDto;
use App\Models\Quote;
use Cknow\Money\Money;

final readonly class CreateQuoteAction
{
    public function __construct(
        private SyncQuoteLinesAction $syncQuoteLinesAction,
    ) {
    }

    public function __invoke(
        QuoteDto $quoteDto,
    ): Quote {
        /** @var Quote $quote */
        $quote = Quote::query()->create([
            'client_id' => $quoteDto->clientId,
            'quote_number' => $this->nextNumber($quoteDto->issueDate->year),
            'issue_date' => $quoteDto->issueDate,
            'validity_date' => $quoteDto->validityDate,
            'quote_status' => $quoteDto->quoteStatus,
            'tva_rate' => $quoteDto->tvaRate,
            'subtotal_ht' => Money::EUR(0),
            'tva_amount' => Money::EUR(0),
            'total_ttc' => Money::EUR(0),
        ]);

        ($this->syncQuoteLinesAction)($quote, $quoteDto->lines, $quoteDto->tvaRate);

        if ($quoteDto->worksiteId !== null) {
            $quote->worksites()->attach($quoteDto->worksiteId);
        }

        return $quote;
    }

    /**
     * DEV-{year}-{sequence}, the sequence restarts every year.
     */
    private function nextNumber(int $year): string
    {
        $prefix = "DEV-{$year}-";

        $last = Quote::withTrashed()
            ->where('quote_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('quote_number')
            ->map(fn (string $number): int => (int) substr($number, strlen($prefix)))
            ->max();

        return $prefix.str_pad((string) (((int) $last) + 1), 3, '0', STR_PAD_LEFT);
    }
}
