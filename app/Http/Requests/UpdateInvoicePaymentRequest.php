<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class UpdateInvoicePaymentRequest extends FormRequest
{
    /**
     * @return array<string, array<int,mixed>|ValidationRule>
     */
    public function rules(): array
    {
        $received = [PaymentStatus::Paid->value, PaymentStatus::Partial->value];

        return [
            'payment_status' => [
                'required',
                Rule::enum(PaymentStatus::class),
            ],
            'payment_date' => [
                Rule::requiredIf(in_array($this->input('payment_status'), $received, true)),
                'nullable',
                'date',
            ],
            'payment_method' => [
                Rule::requiredIf(in_array($this->input('payment_status'), $received, true)),
                'nullable',
                Rule::enum(PaymentMethod::class),
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
            'payment_status' => 'Statut de paiement',
            'payment_date' => 'Date de paiement',
            'payment_method' => 'Mode de paiement',
        ];
    }
}
