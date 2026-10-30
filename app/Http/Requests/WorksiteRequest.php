<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\QuoteStatus;
use App\Enums\WorksitePriority;
use App\Enums\WorksiteStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class WorksiteRequest extends FormRequest
{
    /**
     * @return array<string, array|string|ValidationRule>
     */
    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                'exists:clients,id',
            ],
            // An accepted quote of the same client is required to open a worksite.
            'quote_id' => [
                Rule::requiredIf($this->isMethod('post')),
                'nullable',
                'integer',
                Rule::exists('quotes', 'id')
                    ->where('quote_status', QuoteStatus::Accept->value)
                    ->where('client_id', $this->integer('client_id')),
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'name_worksite' => [
                'nullable',
                'string',
                'min:2',
                'max:55',
            ],
            'description' => [
                'nullable',
                'string',
                'min:2',
                'max:255',
            ],
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
            ],
            'worksite_priority' => [
                'required',
                Rule::enum(WorksitePriority::class),
            ],
            'worksite_status' => [
                'required',
                Rule::enum(WorksiteStatus::class),
            ],
            'street' => [
                'nullable',
                'string',
                'max:55',
            ],
            'city' => [
                'nullable',
                'string',
                'max:25',
            ],
            'zip_code' => [
                'nullable',
                'integer',
            ],
            'country' => [
                'nullable',
                'string',
                'max:25',
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
            'client_id' => 'Client',
            'quote_id' => 'Devis accepté lié',
            'user_id' => 'Responsable du chantier',
            'name_worksite' => 'Nom du chantier',
            'name' => 'Le nom du chantier',
            'description' => 'Description',
            'start_date' => 'Date prévu de début',
            'end_date' => 'Date prévu de fin ',
            'priority' => 'Priorité',
            'worksite_priority' => 'Priorité',
            'status' => 'WorksiteStatus',
            'worksite_status' => 'Statut',
            'street' => 'Rue',
            'city' => 'Ville',
            'zip_code' => 'Code postal',
            'country' => 'Pays',
        ];
    }
}
