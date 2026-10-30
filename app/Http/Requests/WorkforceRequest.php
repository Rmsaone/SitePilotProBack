<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class WorkforceRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'max:20',
            ],
            'worker' => [
                'required',
                'string',
                'max:255',
            ],
            'hr_working' => [
                'required',
                'numeric',
                'min:0',
                'max:99999',
            ],
            'hr_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:9999',
            ],
            'additional_costs' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999',
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
            'code' => 'Code salarié',
            'worker' => 'Nom complet',
            'hr_working' => 'Heures travaillées',
            'hr_rate' => 'Taux horaire',
            'additional_costs' => 'Coûts annexes',
        ];
    }
}
