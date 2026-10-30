<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private const string PASSWORD = 'Secret#2026';

    protected function setUp(): void
    {
        parent::setUp();

        // Sanctum only starts a session for requests coming from a stateful domain (the SPA).
        $this->withHeader('Referer', 'http://localhost:3000');
    }

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

    public function test_should_register_user_and_open_session(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->registerPayload());

        $response->assertStatus(201);
        $response->assertJsonMissingPath('token');
        $response->assertJsonStructure([
            'user' => ['id', 'first_name', 'last_name', 'email', 'phone', 'affiliation_code', 'user_role', 'user_role_title'],
        ]);
        $response->assertJsonPath('user.affiliation_code', 'BTP-2026-ABCD');
        $response->assertJsonPath('user.user_role', UserRole::User->value);

        $this->assertDatabaseHas('users', ['email' => 'jean.dupont@exemple.fr']);
        $this->assertAuthenticatedAs(User::query()->where('email', 'jean.dupont@exemple.fr')->firstOrFail(), 'web');
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
        $response->assertJsonMissingPath('token');
        $response->assertJsonStructure(['user' => ['id', 'email']]);
        $response->assertJsonPath('user.id', $user->id);
        $this->assertAuthenticatedAs($user, 'web');
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
        $this->assertGuest('web');
    }

    public function test_should_return_401_on_me_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_should_return_authenticated_user_on_me(): void
    {
        $user = UserFactory::new()->createOne();

        $response = $this->actingAs($user, 'web')->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $user->id);
        $response->assertJsonPath('data.email', $user->email);
    }

    public function test_should_close_session_on_logout(): void
    {
        $user = UserFactory::new()->createOne();

        $this->actingAs($user, 'web')->postJson('/api/v1/auth/logout')->assertStatus(204);

        $this->assertGuest('web');
    }

    public function test_should_drop_all_sessions_on_logout_everywhere(): void
    {
        /** @var User $user */
        $user = UserFactory::new()->createOne();
        $user->createToken('other-device');
        DB::table('sessions')->insert([
            'id' => 'other-device-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => '',
            'last_activity' => now()->getTimestamp(),
        ]);

        $this->actingAs($user, 'web')->postJson('/api/v1/auth/logout-everywhere')->assertStatus(204);

        $this->assertGuest('web');
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertSame(0, $user->tokens()->count());
    }
}
