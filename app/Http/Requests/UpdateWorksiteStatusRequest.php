<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\WorksiteStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class UpdateWorksiteStatusRequest extends FormRequest
{
    /**
     * @return array<string, array<int,mixed>|ValidationRule>
     */
    public function rules(): array
    {
        return [
            'worksite_status' => [
                'required',
                Rule::enum(WorksiteStatus::class),
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
            'worksite_status' => 'Statut',
        ];
    }
}
