<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\Dto\RegisterDto;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * @internal
 */
#[CoversClass(RegisterUserAction::class)]
final class RegisterUserActionTest extends TestCase
{
    use DatabaseTransactions;

    private function makeDto(array $overrides = []): RegisterDto
    {
        return new RegisterDto(
            firstName: $overrides['firstName'] ?? 'Jean',
            lastName: $overrides['lastName'] ?? 'Dupont',
            email: $overrides['email'] ?? 'jean.dupont@exemple.fr',
            phone: $overrides['phone'] ?? '0612345678',
            password: $overrides['password'] ?? 'Secret#2026',
            affiliationCode: $overrides['affiliationCode'] ?? null,
        );
    }

    #[TestDox(
        'Given a valid RegisterDto
        When I call RegisterUserAction
        Then a user should be persisted with the default role'
    )]
    public function test_should_persist_user_with_default_role(): void
    {
        $user = (new RegisterUserAction())($this->makeDto());

        $this->assertNotNull($user->id);
        $this->assertSame(UserRole::User, $user->userRole);
        $this->assertDatabaseHas('users', ['email' => 'jean.dupont@exemple.fr']);
    }

    #[TestDox(
        'Given a RegisterDto with a plain password
        When I call RegisterUserAction
        Then the password should be stored hashed'
    )]
    public function test_should_hash_password(): void
    {
        $user = (new RegisterUserAction())($this->makeDto());

        $this->assertNotSame('Secret#2026', $user->password);
        $this->assertTrue(Hash::check('Secret#2026', $user->password));
    }

    #[TestDox(
        'Given a RegisterDto with an affiliation code
        When I call RegisterUserAction
        Then the affiliation code should be stored'
    )]
    public function test_should_store_affiliation_code(): void
    {
        $user = (new RegisterUserAction())($this->makeDto(['affiliationCode' => 'BTP-2026-ABCD']));

        $this->assertSame('BTP-2026-ABCD', $user->affiliation_code);
    }
}
