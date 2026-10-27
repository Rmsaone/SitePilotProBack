<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\User\UpdatePasswordAction;
use App\Actions\User\UpdateProfileAction;
use App\Dto\ProfileDto;
use App\Models\User;

class ProfileService
{
    public function update(User $user, ProfileDto $profileDto): User
    {
        /** @var User $updatedUser */
        $updatedUser = app()->call(UpdateProfileAction::class, [
            'user' => $user,
            'profileDto' => $profileDto,
        ]);

        return $updatedUser;
    }

    public function updatePassword(User $user, string $password): User
    {
        /** @var User $updatedUser */
        $updatedUser = app()->call(UpdatePasswordAction::class, [
            'user' => $user,
            'password' => $password,
        ]);

        return $updatedUser;
    }
}
