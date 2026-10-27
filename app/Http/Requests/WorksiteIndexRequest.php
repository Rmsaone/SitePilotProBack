<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\WorksiteStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class WorksiteIndexRequest extends FormRequest
{
    public const array SORTABLE = ['name_worksite', 'start_date', 'end_date', 'created_at'];

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
            'worksite_status' => [
                'nullable',
                Rule::enum(WorksiteStatus::class),
            ],
            'client_id' => [
                'nullable',
                'integer',
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
            'worksite_status' => 'Statut',
            'client_id' => 'Client',
            'sort' => 'Tri',
            'direction' => 'Ordre',
            'per_page' => 'Nombre par page',
        ];
    }
}
