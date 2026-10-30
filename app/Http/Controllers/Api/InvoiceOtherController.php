<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\PurchaseInvoice\StorePurchaseInvoiceAttachment;
use App\Dto\PurchaseInvoiceDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseInvoiceRequest;
use App\Http\Requests\WorksiteScopedIndexRequest;
use App\Http\Resources\InvoiceGen\InvoiceGenResource;
use App\Models\InvoiceOther;
use App\Services\InvoiceOtherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceOtherController extends Controller
{
    public function __construct(
        private readonly InvoiceOtherService $invoiceOtherService,
    ) {
    }

    public function index(WorksiteScopedIndexRequest $worksiteScopedIndexRequest): JsonResponse
    {
        return InvoiceGenResource::collection(
            $this->invoiceOtherService->list(
                $worksiteScopedIndexRequest->worksiteId(),
                $worksiteScopedIndexRequest->perPage(),
            )
        )->response();
    }

    public function store(PurchaseInvoiceRequest $purchaseInvoiceRequest): JsonResponse
    {
        $validated = $purchaseInvoiceRequest->safe()->toArray();

        /** @var InvoiceOther $invoice */
        $invoice = DB::transaction(
            fn () => $this->invoiceOtherService->store(
                PurchaseInvoiceDto::fromArray($validated)
            )
        );

        return InvoiceGenResource::make($invoice)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        int $invoice_other_id,
        PurchaseInvoiceRequest $purchaseInvoiceRequest
    ): JsonResponse {
        $validated = $purchaseInvoiceRequest->safe()->toArray();

        /** @var InvoiceOther $invoice */
        $invoice = InvoiceOther::query()->findOrFail($invoice_other_id);

        $updatedInvoice = DB::transaction(
            fn () => $this->invoiceOtherService->update(
                $invoice,
                PurchaseInvoiceDto::fromArray($validated)
            )
        );

        return InvoiceGenResource::make($updatedInvoice)->response();
    }

    public function attachment(int $invoice_other_id): StreamedResponse
    {
        /** @var InvoiceOther $invoice */
        $invoice = InvoiceOther::query()->findOrFail($invoice_other_id);

        return StorePurchaseInvoiceAttachment::download($invoice);
    }

    public function destroy(int $invoice_other_id): JsonResponse
    {
        /** @var InvoiceOther $invoice */
        $invoice = InvoiceOther::query()->findOrFail($invoice_other_id);

        $this->invoiceOtherService->destroy($invoice);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
