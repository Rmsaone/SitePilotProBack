<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Quote\CreateQuoteAction;
use App\Actions\Quote\UpdateQuoteAction;
use App\Actions\Quote\UpdateQuoteStatusAction;
use App\Dto\QuoteDto;
use App\Dto\QuoteFilterDto;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuoteService
{
    public function list(QuoteFilterDto $quoteFilterDto): LengthAwarePaginator
    {
        return Quote::query()
            ->with([
                'client',
                'worksites',
            ])
            ->withCount('productLines')
            ->when($quoteFilterDto->search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('client', function (Builder $query) use ($search): void {
                        $query->whereLike('company', "%{$search}%")
                            ->orWhereLike('contact_name', "%{$search}%");
                    })
                        ->orWhereLike('quote_number', "%{$search}%");
                });
            })
            ->when($quoteFilterDto->worksiteId, fn (Builder $query, int $id) => $query->whereHas(
                'worksites',
                fn (Builder $query) => $query->where('worksites.id', $id)
            ))
            ->when($quoteFilterDto->clientId, fn (Builder $query, int $id) => $query->where('client_id', $id))
            ->when($quoteFilterDto->quoteStatus, fn (Builder $query, QuoteStatus $status) => $query->where('quote_status', $status))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate($quoteFilterDto->perPage);
    }

    public function store(QuoteDto $quoteDto): Quote
    {
        /** @var Quote $quote */
        $quote = app()->call(CreateQuoteAction::class, [
            'quoteDto' => $quoteDto,
        ]);

        return $quote->refresh();
    }

    public function update(Quote $quote, QuoteDto $quoteDto): Quote
    {
        /** @var Quote $updatedQuote */
        $updatedQuote = app()->call(UpdateQuoteAction::class, [
            'quote' => $quote,
            'quoteDto' => $quoteDto,
        ]);

        return $updatedQuote;
    }

    public function updateStatus(Quote $quote, QuoteStatus $quoteStatus): Quote
    {
        /** @var Quote $updatedQuote */
        $updatedQuote = app()->call(UpdateQuoteStatusAction::class, [
            'quote' => $quote,
            'quoteStatus' => $quoteStatus,
        ]);

        return $updatedQuote;
    }

    public function destroy(Quote $quote): void
    {
        $quote->delete();
    }
}
