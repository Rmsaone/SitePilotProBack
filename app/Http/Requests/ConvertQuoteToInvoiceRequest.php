<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Quote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class ConvertQuoteToInvoiceRequest extends FormRequest
{
    /**
     * @return array<string, array<int,mixed>|ValidationRule>
     */
    public function rules(): array
    {
        $clientId = Quote::query()->whereKey((int) $this->route('quote_id'))->value('client_id');

        return [
            // Defaults to the first worksite linked to the quote.
            'worksite_id' => [
                'nullable',
                'integer',
                Rule::exists('worksites', 'id')->where('client_id', $clientId),
            ],
            'delivery_date' => [
                'nullable',
                'date',
            ],
            'invoice_description' => [
                'nullable',
                'string',
                'max:255',
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
            'worksite_id' => 'Chantier',
            'delivery_date' => "Date d'émission",
            'invoice_description' => 'Description',
        ];
    }
}
