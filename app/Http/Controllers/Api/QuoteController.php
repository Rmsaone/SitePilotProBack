<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\WorksiteScopedIndexRequest;
use App\Http\Resources\Quote\IndexQuoteResource;
use App\Http\Resources\Quote\ShowQuoteResource;
use App\Models\Quote;
use App\Services\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __construct(
        private readonly QuoteService $quoteService,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(WorksiteScopedIndexRequest $worksiteScopedIndexRequest): JsonResponse
    {
        $quoteStatus = $worksiteScopedIndexRequest->filled('quote_status')
            ? QuoteStatus::tryFrom($worksiteScopedIndexRequest->string('quote_status')->toString())
            : null;

        return IndexQuoteResource::collection(
            $this->quoteService->list(
                $worksiteScopedIndexRequest->worksiteId(),
                $quoteStatus,
                $worksiteScopedIndexRequest->perPage(15),
            )
        )->response();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show(int $quote_id)
    {
        /** @var Quote $quote */
        $quote = Quote::query()->findOrFail($quote_id);

        $quote->loadMissing('client', 'worksites', 'productLines');

        return ShowQuoteResource::make($quote)->response();

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Quote $quote)
    {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Quote $quote)
    {

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Quote $quote)
    {

    }
}
