<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Enums\UserRole;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private const string PASSWORD = 'Secret#2026';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::new()->createOne([
            'userRole' => UserRole::User,
            'password' => self::PASSWORD,
        ]);
    }

    /**
     * @return array<string,string>
     */
    private function profilePayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Marie',
            'last_name' => 'Curie',
            'email' => 'marie.curie@exemple.fr',
            'phone' => '0699887766',
        ], $overrides);
    }

    public function test_should_return_401_when_unauthenticated(): void
    {
        $this->putJson('/api/v1/profile', $this->profilePayload())->assertStatus(401);
    }

    public function test_should_update_profile(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/profile', $this->profilePayload());

        $response->assertStatus(200);
        $response->assertJsonPath('data.first_name', 'Marie');
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => 'marie.curie@exemple.fr',
        ]);
    }

    public function test_should_keep_own_email_without_unique_error(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/profile', $this->profilePayload([
            'email' => $this->user->email,
        ]));

        $response->assertStatus(200);
    }

    public function test_should_reject_email_used_by_another_user(): void
    {
        UserFactory::new()->createOne(['email' => 'taken@exemple.fr']);
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/profile', $this->profilePayload([
            'email' => 'taken@exemple.fr',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_should_forbid_role_change_for_non_admin(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/profile', $this->profilePayload([
            'user_role' => UserRole::Admin->value,
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('user_role');
    }

    public function test_should_allow_role_change_for_admin(): void
    {
        $admin = UserFactory::new()->createOne(['userRole' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/v1/profile', $this->profilePayload([
            'user_role' => UserRole::User->value,
        ]));

        $response->assertStatus(200);
        $response->assertJsonPath('data.user_role', UserRole::User->value);
    }

    public function test_should_update_password(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/profile/password', [
            'current_password' => self::PASSWORD,
            'password' => 'NewSecret#2027',
            'password_confirmation' => 'NewSecret#2027',
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('NewSecret#2027', $this->user->refresh()->password));
    }

    public function test_should_reject_password_update_with_wrong_current_password(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/profile/password', [
            'current_password' => 'Wrong#2026',
            'password' => 'NewSecret#2027',
            'password_confirmation' => 'NewSecret#2027',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('current_password');
    }
}
