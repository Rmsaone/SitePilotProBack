<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InvoiceClient;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InvoiceClientService
{
    public function list(?int $worksiteId = null, int $perPage = 20): LengthAwarePaginator
    {
        return InvoiceClient::query()
            ->with('client', 'worksite', 'quote')
            ->when($worksiteId, fn (Builder $query, int $id) => $query->where('worksite_id', $id))
            ->orderByDesc('delivery_date')
            ->paginate($perPage);
    }

    public function destroy(InvoiceClient $invoiceClient): void
    {
        $invoiceClient->delete();
    }
}
