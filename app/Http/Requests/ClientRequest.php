<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ClientType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class ClientRequest extends FormRequest
{
    /**
     * @return array<string, array<int,mixed>|ValidationRule>
     */
    public function rules(): array
    {
        $clientId = $this->route('client_id');
        $isPro = $this->input('client_type') === ClientType::Pro->value;

        return [
            'client_type' => [
                'required',
                Rule::enum(ClientType::class),
            ],
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'contact_name' => [
                'required',
                'string',
                'max:255',
            ],
            'private_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($clientId),
            ],
            'company' => [
                Rule::requiredIf($isPro),
                'nullable',
                'string',
                'max:255',
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
            ],
            'street' => [
                'required',
                'string',
                'max:255',
            ],
            'city' => [
                'required',
                'string',
                'max:255',
            ],
            'zip_code' => [
                'required',
                'integer',
                'digits_between:4,5',
            ],
            'country' => [
                'required',
                'string',
                'max:255',
            ],
            // Only companies have a SIRET (14 digits) and an intra-community VAT number.
            'siret' => [
                Rule::requiredIf($isPro),
                'nullable',
                'string',
                'regex:/^\d{14}$/',
                Rule::unique('clients', 'siret')->ignore($clientId),
            ],
            'tva_intra' => [
                Rule::requiredIf($isPro),
                'nullable',
                'string',
                'max:20',
                Rule::unique('clients', 'tva_intra')->ignore($clientId),
            ],
        ];
    }

    /**
     * Spaces are accepted in the form (« 123 456 789 00012 »).
     */
    #[Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'siret' => $this->filled('siret') ? preg_replace('/\s+/', '', (string) $this->input('siret')) : null,
            'tva_intra' => $this->filled('tva_intra') ? mb_strtoupper((string) preg_replace('/\s+/', '', (string) $this->input('tva_intra'))) : null,
        ]);
    }

    /**
     * @return array<string,string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'siret.regex' => 'Le SIRET doit contenir 14 chiffres.',
        ];
    }

    /**
     * @return array<string,string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'client_type' => 'Type de client',
            'user_id' => 'Responsable client',
            'contact_name' => 'Nom du contact',
            'private_name' => 'Nom du chantier / dossier',
            'email' => 'Email',
            'company' => 'Nom de la société',
            'phone' => 'Téléphone',
            'street' => 'Rue',
            'city' => 'Ville',
            'zip_code' => 'Code postal',
            'country' => 'Pays',
            'siret' => 'N° SIRET',
            'tva_intra' => 'N° TVA intracommunautaire',
        ];
    }
}
