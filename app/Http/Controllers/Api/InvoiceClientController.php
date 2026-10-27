<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorksiteScopedIndexRequest;
use App\Http\Resources\InvoiceClient\InvoiceClientResource;
use App\Services\InvoiceClientService;
use Illuminate\Http\JsonResponse;

class InvoiceClientController extends Controller
{
    public function __construct(
        private readonly InvoiceClientService $invoiceClientService,
    ) {
    }

    public function index(WorksiteScopedIndexRequest $worksiteScopedIndexRequest): JsonResponse
    {
        return InvoiceClientResource::collection(
            $this->invoiceClientService->list(
                $worksiteScopedIndexRequest->worksiteId(),
                $worksiteScopedIndexRequest->perPage(),
            )
        )->response();
    }
}
