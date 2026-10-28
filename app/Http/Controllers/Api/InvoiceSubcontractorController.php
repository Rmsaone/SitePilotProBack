<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\PurchaseInvoice\StorePurchaseInvoiceAttachment;
use App\Dto\PurchaseInvoiceDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseInvoiceRequest;
use App\Http\Requests\WorksiteScopedIndexRequest;
use App\Http\Resources\InvoiceGen\InvoiceGenResource;
use App\Models\InvoiceSubcontractor;
use App\Services\InvoiceSubcontractorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceSubcontractorController extends Controller
{
    public function __construct(
        private readonly InvoiceSubcontractorService $invoiceSubcontractorService,
    ) {
    }

    public function index(WorksiteScopedIndexRequest $worksiteScopedIndexRequest): JsonResponse
    {
        return InvoiceGenResource::collection(
            $this->invoiceSubcontractorService->list(
                $worksiteScopedIndexRequest->worksiteId(),
                $worksiteScopedIndexRequest->perPage(),
            )
        )->response();
    }

    public function store(PurchaseInvoiceRequest $purchaseInvoiceRequest): JsonResponse
    {
        $validated = $purchaseInvoiceRequest->safe()->toArray();

        /** @var InvoiceSubcontractor $invoice */
        $invoice = DB::transaction(
            fn () => $this->invoiceSubcontractorService->store(
                PurchaseInvoiceDto::fromArray($validated)
            )
        );

        return InvoiceGenResource::make($invoice)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        int $invoice_subcontractor_id,
        PurchaseInvoiceRequest $purchaseInvoiceRequest
    ): JsonResponse {
        $validated = $purchaseInvoiceRequest->safe()->toArray();

        /** @var InvoiceSubcontractor $invoice */
        $invoice = InvoiceSubcontractor::query()->findOrFail($invoice_subcontractor_id);

        $updatedInvoice = DB::transaction(
            fn () => $this->invoiceSubcontractorService->update(
                $invoice,
                PurchaseInvoiceDto::fromArray($validated)
            )
        );

        return InvoiceGenResource::make($updatedInvoice)->response();
    }

    public function attachment(int $invoice_subcontractor_id): StreamedResponse
    {
        /** @var InvoiceSubcontractor $invoice */
        $invoice = InvoiceSubcontractor::query()->findOrFail($invoice_subcontractor_id);

        return StorePurchaseInvoiceAttachment::download($invoice);
    }

    public function destroy(int $invoice_subcontractor_id): JsonResponse
    {
        /** @var InvoiceSubcontractor $invoice */
        $invoice = InvoiceSubcontractor::query()->findOrFail($invoice_subcontractor_id);

        $this->invoiceSubcontractorService->destroy($invoice);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
