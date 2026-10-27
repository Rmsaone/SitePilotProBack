<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuoteService
{
    public function list(
        ?int $worksiteId = null,
        ?QuoteStatus $quoteStatus = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return Quote::query()
            ->with([
                'client',
                'productLines',
                'worksites',
            ])
            ->withCount('productLines')
            ->when($worksiteId, fn (Builder $query, int $id) => $query->whereHas(
                'worksites',
                fn (Builder $query) => $query->where('worksites.id', $id)
            ))
            ->when($quoteStatus, fn (Builder $query, QuoteStatus $status) => $query->where('quote_status', $status))
            ->latest()
            ->paginate($perPage);
    }

    //    public function store(Quote $quote): Quote
    //    {
    //        /** @var Quote $createQuote */
    //
    //
    //        $createQuote->refresh();
    //
    //        return $createQuote;
    //    }
    //
    //    public function update(
    //        Quote $quote,
    //        QuoteDto $quoteDto
    //    ): Quote {
    //        /** @var Quote $updateQuote */
    //
    //        $updateQuote->refresh();
    //
    //        return $updateQuote;
    //    }

    public function destroy(Quote $quote): void
    {
        $quote->delete();
    }
}
