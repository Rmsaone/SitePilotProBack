<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    /**
     * Authenticates a user on the sanctum guard for API calls.
     */
    protected function actingAsUser(?User $user = null): User
    {
        $user ??= UserFactory::new()->createOne();

        Sanctum::actingAs($user);

        return $user;
    }
}
