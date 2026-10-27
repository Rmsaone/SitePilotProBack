<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\QuoteStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

/**
 * Query string shared by the lists displayed in a worksite tab.
 */
class WorksiteScopedIndexRequest extends FormRequest
{
    /**
     * @return array<string, array<int,mixed>|ValidationRule>
     */
    public function rules(): array
    {
        return [
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
            'quote_status' => [
                'nullable',
                Rule::enum(QuoteStatus::class),
            ],
        ];
    }

    public function worksiteId(): ?int
    {
        return $this->filled('worksite_id') ? $this->integer('worksite_id') : null;
    }

    public function perPage(int $default = 20): int
    {
        return $this->filled('per_page') ? $this->integer('per_page') : $default;
    }

    /**
     * @return array<string,string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'worksite_id' => 'Chantier',
            'per_page' => 'Nombre par page',
            'quote_status' => 'Statut du devis',
        ];
    }
}
