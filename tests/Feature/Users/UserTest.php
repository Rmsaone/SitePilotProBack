<?php

declare(strict_types=1);

namespace Tests\Feature\Users;

use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_return_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/users')->assertStatus(401);
    }

    public function test_should_list_users_for_responsible_select(): void
    {
        $this->actingAsUser(UserFactory::new()->createOne(['first_name' => 'Jean', 'last_name' => 'Dupont']));
        UserFactory::new()->createOne();

        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'full_name', 'user_role', 'user_role_title']]]);
    }
}
