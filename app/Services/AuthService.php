<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Auth\RegisterUserAction;
use App\Dto\RegisterDto;
use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;

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

    /**
     * Opens a session for the SPA: Sanctum then authenticates it through the session cookie.
     */
    public function login(Request $request, User $user): void
    {
        $this->sessionGuard()->login($user);

        $request->session()->regenerate();
    }

    public function logout(Request $request): void
    {
        $this->sessionGuard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Drops every session of the user (database session driver) and any leftover API token.
     */
    public function logoutEverywhere(Request $request, User $user): void
    {
        DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();

        $user->tokens()->delete();

        $this->logout($request);
    }

    private function sessionGuard(): StatefulGuard
    {
        $guard = Auth::guard('web');

        if (! $guard instanceof StatefulGuard) {
            throw new LogicException('The web guard must be session based for SPA authentication.');
        }

        return $guard;
    }
}
