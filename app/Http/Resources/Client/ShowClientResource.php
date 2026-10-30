<?php

declare(strict_types=1);

namespace App\Http\Resources\Client;

use App\Models\Client;
use Illuminate\Http\Request;
use Override;

/**
 * @property Client $resource
 */
final class ShowClientResource extends IndexClientResource
{
    /**
     * @return array<string,mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'street' => $this->resource->street,
            'country' => $this->resource->country,
            'siret' => $this->resource->siret,
            'tva_intra' => $this->resource->tva_intra,
            'created_at' => $this->resource->created_at->toDateString(),
        ];
    }
}
