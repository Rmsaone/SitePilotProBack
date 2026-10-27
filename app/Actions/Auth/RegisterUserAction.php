<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Dto\RegisterDto;
use App\Enums\UserRole;
use App\Models\User;

final class RegisterUserAction
{
    public function __invoke(
        RegisterDto $registerDto,
    ): User {
        return User::query()->create([
            'first_name' => $registerDto->firstName,
            'last_name' => $registerDto->lastName,
            'email' => $registerDto->email,
            'phone' => $registerDto->phone,
            'password' => $registerDto->password,
            'affiliation_code' => $registerDto->affiliationCode,
            'userRole' => UserRole::User,
        ]);
    }
}
