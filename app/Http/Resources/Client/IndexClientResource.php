<?php

declare(strict_types=1);

namespace App\Http\Resources\Client;

use App\Http\Resources\User\IndexUserResource;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property Client $resource
 */
class IndexClientResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'contact_name' => $this->resource->contact_name,
            'private_name' => $this->resource->private_name,
            'email' => $this->resource->email,
            'company' => $this->resource->company,
            'phone' => $this->resource->phone,
            'city' => $this->resource->city,
            'zip_code' => $this->resource->zip_code,
            'client_type' => $this->resource->clientType->value,
            'client_type_title' => $this->resource->clientType->title(),
            'responsible' => $this
                ->whenLoaded(
                    'user',
                    fn () => IndexUserResource::make($this->resource->user)),
            'worksites_count' => $this->whenCounted('worksites'),
            'quotes_count' => $this->whenCounted('quotes'),
            'invoices_count' => $this->whenCounted('invoicesClient'),
        ];
    }
}
