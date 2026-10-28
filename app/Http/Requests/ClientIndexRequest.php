<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ClientType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class ClientIndexRequest extends FormRequest
{
    public const array SORTABLE = ['company', 'contact_name', 'city', 'created_at'];

    /**
     * @return array<string, array<int,mixed>|ValidationRule>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'client_type' => [
                'nullable',
                Rule::enum(ClientType::class),
            ],
            'sort' => [
                'nullable',
                Rule::in(self::SORTABLE),
            ],
            'direction' => [
                'nullable',
                Rule::in(['asc', 'desc']),
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    /**
     * @return array<string,string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'search' => 'Recherche',
            'client_type' => 'Type de client',
            'sort' => 'Tri',
            'direction' => 'Ordre',
            'per_page' => 'Nombre par page',
        ];
    }
}
