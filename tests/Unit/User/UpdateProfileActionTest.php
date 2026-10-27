<?php

declare(strict_types=1);

namespace Tests\Unit\User;

use App\Actions\User\UpdatePasswordAction;
use App\Actions\User\UpdateProfileAction;
use App\Dto\ProfileDto;
use App\Enums\UserRole;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * @internal
 */
#[CoversClass(UpdateProfileAction::class)]
#[CoversClass(UpdatePasswordAction::class)]
final class UpdateProfileActionTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::new()->createOne([
            'userRole' => UserRole::Admin,
        ]);
    }

    private function makeDto(?UserRole $userRole = null): ProfileDto
    {
        return new ProfileDto(
            firstName: 'Marie',
            lastName: 'Curie',
            email: 'marie.curie@exemple.fr',
            phone: '0699887766',
            userRole: $userRole,
        );
    }

    #[TestDox(
        'Given an existing User and a ProfileDto
        When I call UpdateProfileAction
        Then the new values should be persisted'
    )]
    public function test_should_persist_updated_profile(): void
    {
        $result = (new UpdateProfileAction())($this->user, $this->makeDto());

        $this->assertSame('Marie', $result->first_name);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => 'marie.curie@exemple.fr',
        ]);
    }

    #[TestDox(
        'Given a ProfileDto without role
        When I call UpdateProfileAction
        Then the current role should be kept'
    )]
    public function test_should_keep_role_when_not_provided(): void
    {
        $result = (new UpdateProfileAction())($this->user, $this->makeDto());

        $this->assertSame(UserRole::Admin, $result->userRole);
    }

    #[TestDox(
        'Given a ProfileDto with a role
        When I call UpdateProfileAction
        Then the role should be updated'
    )]
    public function test_should_update_role_when_provided(): void
    {
        $result = (new UpdateProfileAction())($this->user, $this->makeDto(UserRole::SiteManager));

        $this->assertSame(UserRole::SiteManager, $result->userRole);
    }

    #[TestDox(
        'Given an existing User and a new password
        When I call UpdatePasswordAction
        Then the hashed password should be updated'
    )]
    public function test_should_update_password(): void
    {
        $result = (new UpdatePasswordAction())($this->user, 'NewSecret#2027');

        $this->assertTrue(Hash::check('NewSecret#2027', $result->password));
    }
}
