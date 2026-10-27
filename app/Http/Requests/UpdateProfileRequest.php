<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, array<int,mixed>|string|ValidationRule>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            'first_name' => [
                'required',
                'string',
                'max:255',
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
            ],
            'user_role' => [
                'sometimes',
                Rule::prohibitedIf(! $user->isAdmin()),
                Rule::enum(UserRole::class),
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
            'first_name' => 'Prénom',
            'last_name' => 'Nom',
            'email' => 'Email',
            'phone' => 'Téléphone',
            'user_role' => 'Rôle',
        ];
    }
}
