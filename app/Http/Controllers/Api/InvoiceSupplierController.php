<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\PurchaseInvoice\StorePurchaseInvoiceAttachment;
use App\Dto\PurchaseInvoiceDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseInvoiceRequest;
use App\Http\Requests\WorksiteScopedIndexRequest;
use App\Http\Resources\InvoiceGen\InvoiceGenResource;
use App\Models\InvoiceSupplier;
use App\Services\InvoiceSupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceSupplierController extends Controller
{
    public function __construct(
        private readonly InvoiceSupplierService $invoiceSupplierService,
    ) {
    }

    public function index(WorksiteScopedIndexRequest $worksiteScopedIndexRequest): JsonResponse
    {
        return InvoiceGenResource::collection(
            $this->invoiceSupplierService->list(
                $worksiteScopedIndexRequest->worksiteId(),
                $worksiteScopedIndexRequest->perPage(),
            )
        )->response();
    }

    public function store(PurchaseInvoiceRequest $purchaseInvoiceRequest): JsonResponse
    {
        $validated = $purchaseInvoiceRequest->safe()->toArray();

        /** @var InvoiceSupplier $invoice */
        $invoice = DB::transaction(
            fn () => $this->invoiceSupplierService->store(
                PurchaseInvoiceDto::fromArray($validated)
            )
        );

        return InvoiceGenResource::make($invoice)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        int $invoice_supplier_id,
        PurchaseInvoiceRequest $purchaseInvoiceRequest
    ): JsonResponse {
        $validated = $purchaseInvoiceRequest->safe()->toArray();

        /** @var InvoiceSupplier $invoice */
        $invoice = InvoiceSupplier::query()->findOrFail($invoice_supplier_id);

        $updatedInvoice = DB::transaction(
            fn () => $this->invoiceSupplierService->update(
                $invoice,
                PurchaseInvoiceDto::fromArray($validated)
            )
        );

        return InvoiceGenResource::make($updatedInvoice)->response();
    }

    public function attachment(int $invoice_supplier_id): StreamedResponse
    {
        /** @var InvoiceSupplier $invoice */
        $invoice = InvoiceSupplier::query()->findOrFail($invoice_supplier_id);

        return StorePurchaseInvoiceAttachment::download($invoice);
    }

    public function destroy(int $invoice_supplier_id): JsonResponse
    {
        /** @var InvoiceSupplier $invoice */
        $invoice = InvoiceSupplier::query()->findOrFail($invoice_supplier_id);

        $this->invoiceSupplierService->destroy($invoice);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
