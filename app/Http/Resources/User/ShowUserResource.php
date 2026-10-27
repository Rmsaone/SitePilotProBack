<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property User $resource
 */
final class ShowUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'affiliation_code' => $this->resource->affiliation_code,
            'user_role' => $this->resource->userRole->value,
            'user_role_title' => $this->resource->userRole->title(),
            'created_at' => $this->resource->created_at,
        ];
    }
}
