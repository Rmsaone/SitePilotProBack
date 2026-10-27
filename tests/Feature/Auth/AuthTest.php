<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private const string PASSWORD = 'Secret#2026';

    /**
     * @return array<string,string>
     */
    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean.dupont@exemple.fr',
            'phone' => '0612345678',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'affiliation_code' => 'btp-2026-abcd',
        ], $overrides);
    }

    public function test_should_register_user_and_return_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->registerPayload());

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'token',
            'user' => ['id', 'first_name', 'last_name', 'email', 'phone', 'affiliation_code', 'user_role', 'user_role_title'],
        ]);
        $response->assertJsonPath('user.affiliation_code', 'BTP-2026-ABCD');
        $response->assertJsonPath('user.user_role', UserRole::User->value);

        $this->assertDatabaseHas('users', ['email' => 'jean.dupont@exemple.fr']);
    }

    public function test_should_reject_register_with_existing_email(): void
    {
        UserFactory::new()->createOne(['email' => 'jean.dupont@exemple.fr']);

        $response = $this->postJson('/api/v1/auth/register', $this->registerPayload());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_should_reject_register_with_weak_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->registerPayload([
            'password' => 'password',
            'password_confirmation' => 'password',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    public function test_should_reject_register_with_mismatched_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->registerPayload([
            'password_confirmation' => 'Other#2026',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    public function test_should_login_with_valid_credentials(): void
    {
        $user = UserFactory::new()->createOne(['password' => self::PASSWORD]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user' => ['id', 'email']]);
        $response->assertJsonPath('user.id', $user->id);
    }

    public function test_should_reject_login_with_invalid_credentials(): void
    {
        $user = UserFactory::new()->createOne(['password' => self::PASSWORD]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Wrong#2026',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_should_return_401_on_me_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_should_return_authenticated_user_on_me(): void
    {
        $user = UserFactory::new()->createOne();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $user->id);
        $response->assertJsonPath('data.email', $user->email);
    }

    public function test_should_revoke_current_token_on_logout(): void
    {
        $user = UserFactory::new()->createOne();
        $token = $user->createToken('api-token')->plainTextToken;
        $user->createToken('other-device');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertStatus(204);

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_should_revoke_all_tokens_on_logout_everywhere(): void
    {
        /** @var User $user */
        $user = UserFactory::new()->createOne();
        $token = $user->createToken('api-token')->plainTextToken;
        $user->createToken('other-device');

        $this->withToken($token)->postJson('/api/v1/auth/logout-everywhere')->assertStatus(204);

        $this->assertSame(0, $user->tokens()->count());
    }
}
