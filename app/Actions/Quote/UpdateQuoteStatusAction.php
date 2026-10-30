<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\Enums\QuoteStatus;
use App\Models\Quote;

final class UpdateQuoteStatusAction
{
    public function __invoke(
        Quote $quote,
        QuoteStatus $quoteStatus,
    ): Quote {
        $quote->update([
            'quote_status' => $quoteStatus,
        ]);

        return $quote->refresh();
    }
}
