<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\QuoteStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class UpdateQuoteStatusRequest extends FormRequest
{
    /**
     * @return array<string, array<int,mixed>|ValidationRule>
     */
    public function rules(): array
    {
        return [
            'quote_status' => [
                'required',
                Rule::enum(QuoteStatus::class),
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
            'quote_status' => 'Statut',
        ];
    }
}
