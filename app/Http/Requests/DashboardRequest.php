<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DashboardPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class DashboardRequest extends FormRequest
{
    /**
     * @return array<string, array<int,mixed>|ValidationRule>
     */
    public function rules(): array
    {
        return [
            'period' => [
                'nullable',
                Rule::enum(DashboardPeriod::class),
            ],
        ];
    }

    public function period(): DashboardPeriod
    {
        return DashboardPeriod::tryFrom((string) $this->input('period')) ?? DashboardPeriod::Month;
    }

    /**
     * @return array<string,string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'period' => 'Période',
        ];
    }
}
