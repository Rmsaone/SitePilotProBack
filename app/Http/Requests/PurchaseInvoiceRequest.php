<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class PurchaseInvoiceRequest extends FormRequest
{
    /**
     * @return array<string, array<int,string>>
     */
    public function rules(): array
    {
        return [
            'worksite_id' => [
                'required',
                'integer',
                'exists:worksites,id',
            ],
            'delivery_date' => [
                'required',
                'date',
            ],
            'number_invoice' => [
                'required',
                'string',
                'max:55',
            ],
            'invoice_description' => [
                'required',
                'string',
                'max:255',
            ],
            'purchase_price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999',
            ],
            'cost_price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999',
            ],
            'attachment' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240',
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
            'delivery_date' => 'Date de facture',
            'number_invoice' => 'N° de facture',
            'invoice_description' => 'Libellé',
            'purchase_price' => "Prix d'achat HT",
            'cost_price' => 'Prix de revient HT',
            'attachment' => 'Pièce jointe',
        ];
    }
}
