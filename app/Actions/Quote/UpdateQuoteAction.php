<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\Dto\QuoteDto;
use App\Models\Quote;

final readonly class UpdateQuoteAction
{
    public function __construct(
        private SyncQuoteLinesAction $syncQuoteLinesAction,
    ) {
    }

    public function __invoke(
        Quote $quote,
        QuoteDto $quoteDto,
    ): Quote {
        $quote->update([
            'client_id' => $quoteDto->clientId,
            'issue_date' => $quoteDto->issueDate,
            'validity_date' => $quoteDto->validityDate,
            'quote_status' => $quoteDto->quoteStatus,
        ]);

        ($this->syncQuoteLinesAction)($quote, $quoteDto->lines, $quoteDto->tvaRate);

        if ($quoteDto->worksiteId !== null) {
            $quote->worksites()->syncWithoutDetaching([$quoteDto->worksiteId]);
        }

        return $quote->refresh();
    }
}
