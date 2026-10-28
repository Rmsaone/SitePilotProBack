<?php

declare(strict_types=1);

namespace Tests\Feature\Clients;

use App\Enums\ClientType;
use App\Models\User;
use Database\Factories\ClientFactory;
use Database\Factories\QuoteFactory;
use Database\Factories\WorksiteFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->actingAsUser();
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_type' => ClientType::Pro->value,
            'user_id' => $this->user->id,
            'contact_name' => 'Jean-Pierre Rousseau',
            'private_name' => 'Aile Ouest',
            'email' => 'jp.rousseau@mairie-limoges.fr',
            'company' => 'Mairie de Limoges',
            'phone' => '05 55 45 55 45',
            'street' => '9 place Léon Betoulle',
            'city' => 'Limoges',
            'zip_code' => 87000,
            'country' => 'France',
            'siret' => '217 300 850 00012',
            'tva_intra' => 'fr 12 217300850',
        ], $overrides);
    }

    public function test_should_create_a_professional_client(): void
    {
        $this->postJson('/api/v1/clients', $this->payload())
            ->assertStatus(201)
            ->assertJsonPath('data.company', 'Mairie de Limoges')
            ->assertJsonPath('data.client_type', ClientType::Pro->value)
            ->assertJsonPath('data.client_type_title', 'Professionnel')
            ->assertJsonPath('data.siret', '21730085000012')
            ->assertJsonPath('data.tva_intra', 'FR12217300850')
            ->assertJsonPath('data.responsible.id', $this->user->id)
            ->assertJsonPath('data.worksites_count', 0);
    }

    public function test_should_create_a_private_client_without_company_identifiers(): void
    {
        $this->postJson('/api/v1/clients', $this->payload([
            'client_type' => ClientType::Private->value,
            'company' => null,
            'siret' => '217 300 850 00012',
            'tva_intra' => null,
        ]))
            ->assertStatus(201)
            ->assertJsonPath('data.siret', null)
            ->assertJsonPath('data.client_type_title', 'Particulier');

        // Several private clients can coexist without SIRET.
        $this->postJson('/api/v1/clients', $this->payload([
            'client_type' => ClientType::Private->value,
            'email' => 'autre@exemple.fr',
            'company' => null,
            'siret' => null,
            'tva_intra' => null,
        ]))->assertStatus(201);
    }

    public function test_should_require_company_identifiers_for_a_professional(): void
    {
        $this->postJson('/api/v1/clients', $this->payload(['company' => null, 'siret' => null, 'tva_intra' => null]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['company', 'siret', 'tva_intra']);

        $this->postJson('/api/v1/clients', $this->payload(['siret' => '1234']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('siret');
    }

    public function test_should_keep_email_siret_and_tva_unique(): void
    {
        ClientFactory::new()->createOne([
            'email' => 'jp.rousseau@mairie-limoges.fr',
            'siret' => '21730085000012',
            'tva_intra' => 'FR12217300850',
        ]);

        $this->postJson('/api/v1/clients', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'siret', 'tva_intra']);
    }

    public function test_should_update_a_client_keeping_its_own_unique_values(): void
    {
        $clientId = $this->postJson('/api/v1/clients', $this->payload())->json('data.id');

        $this->putJson("/api/v1/clients/{$clientId}", $this->payload(['city' => 'Bordeaux', 'zip_code' => 33000]))
            ->assertOk()
            ->assertJsonPath('data.city', 'Bordeaux');
    }

    public function test_should_show_a_client_with_counts(): void
    {
        $client = ClientFactory::new()->createOne();
        WorksiteFactory::new()->count(2)->create(['client_id' => $client->id]);
        QuoteFactory::new()->createOne(['client_id' => $client->id]);

        $this->getJson("/api/v1/clients/{$client->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $client->id)
            ->assertJsonPath('data.worksites_count', 2)
            ->assertJsonPath('data.quotes_count', 1)
            ->assertJsonPath('data.invoices_count', 0);
    }

    public function test_should_delete_a_client_without_activity(): void
    {
        $client = ClientFactory::new()->createOne();

        $this->deleteJson("/api/v1/clients/{$client->id}")->assertNoContent();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_should_refuse_to_delete_a_client_with_worksites(): void
    {
        $client = ClientFactory::new()->createOne();
        WorksiteFactory::new()->createOne(['client_id' => $client->id]);

        $this->deleteJson("/api/v1/clients/{$client->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('client');
    }

    public function test_should_sort_and_filter_clients(): void
    {
        ClientFactory::new()->createOne(['city' => 'Pau', 'clientType' => ClientType::Private, 'company' => null]);
        ClientFactory::new()->createOne(['city' => 'Angoulême']);

        $this->getJson('/api/v1/clients?sort=city&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.city', 'Angoulême');

        $this->getJson('/api/v1/clients?client_type=private')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.city', 'Pau');
    }

    public function test_should_return_404_for_unknown_client(): void
    {
        $this->getJson('/api/v1/clients/999999')->assertNotFound();
        $this->putJson('/api/v1/clients/999999', $this->payload())->assertNotFound();
        $this->deleteJson('/api/v1/clients/999999')->assertNotFound();
    }
}
