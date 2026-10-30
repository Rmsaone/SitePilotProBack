<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaymentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class InvoiceClientIndexRequest extends FormRequest
{
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
            'payment_status' => [
                'nullable',
                Rule::enum(PaymentStatus::class),
            ],
            'client_id' => [
                'nullable',
                'integer',
            ],
            'worksite_id' => [
                'nullable',
                'integer',
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
            'payment_status' => 'Statut de paiement',
            'client_id' => 'Client',
            'worksite_id' => 'Chantier',
            'per_page' => 'Nombre par page',
        ];
    }
}
