<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;

final class UpdatePasswordAction
{
    public function __invoke(
        User $user,
        string $password,
    ): User {
        $user->update([
            'password' => $password,
        ]);

        return $user->refresh();
    }
}
