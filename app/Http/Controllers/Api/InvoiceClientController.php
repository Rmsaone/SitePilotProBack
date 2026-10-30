<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Dto\InvoiceClientFilterDto;
use App\Dto\InvoicePaymentDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceClientIndexRequest;
use App\Http\Requests\UpdateInvoicePaymentRequest;
use App\Http\Resources\InvoiceClient\InvoiceClientResource;
use App\Http\Resources\InvoiceClient\ShowInvoiceClientResource;
use App\Models\InvoiceClient;
use App\Services\InvoiceClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class InvoiceClientController extends Controller
{
    public const array SHOW_RELATIONS = ['client', 'worksite', 'quote.productLines'];

    public function __construct(
        private readonly InvoiceClientService $invoiceClientService,
    ) {
    }

    public function index(InvoiceClientIndexRequest $invoiceClientIndexRequest): JsonResponse
    {
        return InvoiceClientResource::collection(
            $this->invoiceClientService->list(
                InvoiceClientFilterDto::fromArray($invoiceClientIndexRequest->safe()->toArray())
            )
        )->response();
    }

    public function show(int $invoice_client_id): JsonResponse
    {
        /** @var InvoiceClient $invoiceClient */
        $invoiceClient = InvoiceClient::query()->findOrFail($invoice_client_id);

        $invoiceClient->loadMissing(self::SHOW_RELATIONS);

        return ShowInvoiceClientResource::make($invoiceClient)->response();
    }

    public function updatePayment(
        int $invoice_client_id,
        UpdateInvoicePaymentRequest $updateInvoicePaymentRequest
    ): JsonResponse {
        $validated = $updateInvoicePaymentRequest->safe()->toArray();

        /** @var InvoiceClient $invoiceClient */
        $invoiceClient = InvoiceClient::query()->findOrFail($invoice_client_id);

        $updatedInvoice = DB::transaction(
            fn () => $this->invoiceClientService->updatePayment(
                $invoiceClient,
                InvoicePaymentDto::fromArray($validated)
            )
        );

        $updatedInvoice->loadMissing('client');

        return InvoiceClientResource::make($updatedInvoice)->response();
    }

    public function destroy(int $invoice_client_id): JsonResponse
    {
        /** @var InvoiceClient $invoiceClient */
        $invoiceClient = InvoiceClient::query()->findOrFail($invoice_client_id);

        $this->invoiceClientService->destroy($invoiceClient);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
