<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Auth\RegisterUserAction;
use App\Dto\RegisterDto;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public function register(RegisterDto $registerDto): User
    {
        /** @var User $user */
        $user = app()->call(RegisterUserAction::class, [
            'registerDto' => $registerDto,
        ]);

        return $user;
    }

    public function attempt(string $email, string $password): ?User
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function createToken(User $user): string
    {
        return $user->createToken('api-token', [$user->userRole->value])->plainTextToken;
    }

    public function logout(User $user): void
    {
        /** @var PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();

        $token?->delete();
    }

    public function logoutEverywhere(User $user): void
    {
        $user->tokens()->delete();
    }
}
