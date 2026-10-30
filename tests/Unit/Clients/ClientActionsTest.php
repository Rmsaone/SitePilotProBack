<?php

declare(strict_types=1);

namespace Tests\Unit\Clients;

use App\Actions\Client\CreateClientAction;
use App\Actions\Client\DeleteClientAction;
use App\Actions\Client\UpdateClientAction;
use App\Dto\ClientDto;
use App\Enums\ClientType;
use App\Models\User;
use Database\Factories\ClientFactory;
use Database\Factories\QuoteFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * @internal
 */
#[CoversClass(CreateClientAction::class)]
#[CoversClass(UpdateClientAction::class)]
#[CoversClass(DeleteClientAction::class)]
#[CoversClass(ClientDto::class)]
final class ClientActionsTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::new()->createOne();
    }

    private function makeDto(array $overrides = []): ClientDto
    {
        return ClientDto::fromArray(array_merge([
            'client_type' => 'pro',
            'user_id' => $this->user->id,
            'contact_name' => 'Sophie Marchand',
            'email' => 's.marchand@sci-acacias.fr',
            'company' => 'SCI Les Acacias',
            'phone' => '0556789012',
            'street' => '3 rue des Acacias',
            'city' => 'Bordeaux',
            'zip_code' => '33000',
            'country' => 'France',
            'siret' => '12345678900012',
            'tva_intra' => 'FR12345678900',
        ], $overrides));
    }

    #[TestDox(
        'Given a private client payload with a SIRET
        When I build a ClientDto
        Then the company identifiers should be dropped'
    )]
    public function test_should_drop_identifiers_for_private_clients(): void
    {
        $dto = $this->makeDto(['client_type' => 'private']);

        $this->assertSame(ClientType::Private, $dto->clientType);
        $this->assertNull($dto->siret);
        $this->assertNull($dto->tvaIntra);
    }

    #[TestDox(
        'Given a ClientDto
        When I call CreateClientAction then UpdateClientAction
        Then the client should be persisted and updated'
    )]
    public function test_should_create_and_update_client(): void
    {
        $client = (new CreateClientAction())($this->makeDto());

        $this->assertSame($this->user->id, $client->user_id);
        $this->assertSame(ClientType::Pro, $client->refresh()->clientType);

        $updated = (new UpdateClientAction())($client, $this->makeDto(['city' => 'Mérignac']));

        $this->assertSame('Mérignac', $updated->city);
    }

    #[TestDox(
        'Given a client with a quote
        When I call DeleteClientAction
        Then a validation exception should be thrown'
    )]
    public function test_should_not_delete_a_client_with_quotes(): void
    {
        $client = ClientFactory::new()->createOne();
        QuoteFactory::new()->createOne(['client_id' => $client->id]);

        $this->expectException(ValidationException::class);

        (new DeleteClientAction())($client);
    }
}
