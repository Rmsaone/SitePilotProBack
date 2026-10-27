<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Dto\ProfileDto;
use App\Models\User;

final class UpdateProfileAction
{
    public function __invoke(
        User $user,
        ProfileDto $profileDto,
    ): User {
        $user->update([
            'first_name' => $profileDto->firstName,
            'last_name' => $profileDto->lastName,
            'email' => $profileDto->email,
            'phone' => $profileDto->phone,
            'userRole' => $profileDto->userRole ?? $user->userRole,
        ]);

        return $user->refresh();
    }
}
