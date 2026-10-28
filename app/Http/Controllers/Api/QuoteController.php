<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Dto\QuoteDto;
use App\Dto\QuoteFilterDto;
use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConvertQuoteToInvoiceRequest;
use App\Http\Requests\QuoteIndexRequest;
use App\Http\Requests\QuoteRequest;
use App\Http\Requests\UpdateQuoteStatusRequest;
use App\Http\Resources\InvoiceClient\ShowInvoiceClientResource;
use App\Http\Resources\Quote\IndexQuoteResource;
use App\Http\Resources\Quote\ShowQuoteResource;
use App\Models\InvoiceClient;
use App\Models\Quote;
use App\Services\InvoiceClientService;
use App\Services\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class QuoteController extends Controller
{
    private const array SHOW_RELATIONS = ['client', 'worksites', 'productLines'];

    public function __construct(
        private readonly QuoteService $quoteService,
    ) {
    }

    public function index(QuoteIndexRequest $quoteIndexRequest): JsonResponse
    {
        return IndexQuoteResource::collection(
            $this->quoteService->list(
                QuoteFilterDto::fromArray($quoteIndexRequest->safe()->toArray())
            )
        )->response();
    }

    public function show(int $quote_id): JsonResponse
    {
        /** @var Quote $quote */
        $quote = Quote::query()->findOrFail($quote_id);

        $quote->loadMissing(self::SHOW_RELATIONS);

        return ShowQuoteResource::make($quote)->response();
    }

    public function store(QuoteRequest $quoteRequest): JsonResponse
    {
        $validated = $quoteRequest->safe()->toArray();

        /** @var Quote $quote */
        $quote = DB::transaction(
            fn () => $this->quoteService->store(
                QuoteDto::fromArray($validated)
            )
        );

        $quote->loadMissing(self::SHOW_RELATIONS);

        return ShowQuoteResource::make($quote)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        int $quote_id,
        QuoteRequest $quoteRequest
    ): JsonResponse {
        $validated = $quoteRequest->safe()->toArray();

        /** @var Quote $quote */
        $quote = Quote::query()->findOrFail($quote_id);

        $updatedQuote = DB::transaction(
            fn () => $this->quoteService->update(
                $quote,
                QuoteDto::fromArray($validated)
            )
        );

        $updatedQuote->load(self::SHOW_RELATIONS);

        return ShowQuoteResource::make($updatedQuote)->response();
    }

    public function updateStatus(
        int $quote_id,
        UpdateQuoteStatusRequest $updateQuoteStatusRequest
    ): JsonResponse {
        /** @var Quote $quote */
        $quote = Quote::query()->findOrFail($quote_id);

        $updatedQuote = DB::transaction(
            fn () => $this->quoteService->updateStatus(
                $quote,
                QuoteStatus::from((string) $updateQuoteStatusRequest->validated('quote_status'))
            )
        );

        $updatedQuote->loadMissing('client');

        return IndexQuoteResource::make($updatedQuote)->response();
    }

    public function convertToInvoice(
        int $quote_id,
        ConvertQuoteToInvoiceRequest $convertQuoteToInvoiceRequest,
        InvoiceClientService $invoiceClientService,
    ): JsonResponse {
        /** @var Quote $quote */
        $quote = Quote::query()->findOrFail($quote_id);

        $validated = $convertQuoteToInvoiceRequest->safe()->toArray();

        /** @var InvoiceClient $invoice */
        $invoice = DB::transaction(
            fn () => $invoiceClientService->createFromQuote(
                $quote,
                isset($validated['worksite_id']) ? (int) $validated['worksite_id'] : null,
                isset($validated['delivery_date']) ? Carbon::parse($validated['delivery_date']) : null,
                isset($validated['invoice_description']) ? (string) $validated['invoice_description'] : null,
            )
        );

        $invoice->loadMissing(InvoiceClientController::SHOW_RELATIONS);

        return ShowInvoiceClientResource::make($invoice)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(int $quote_id): JsonResponse
    {
        /** @var Quote $quote */
        $quote = Quote::query()->findOrFail($quote_id);

        $this->quoteService->destroy($quote);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
