<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\ClientDto;
use App\Models\Client;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientService
{
    // Retourne la liste de tous les clients.
    public function list(?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        return Client::query()
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereLike('company', "%{$search}%")
                        ->orWhereLike('contact_name', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    // Crée un nouveau client.
    public function store(ClientDto $clientDto): Client
    {
        return Client::query()->create([
            'contact_name' => $clientDto->contactName,
            'private_name' => $clientDto->privateName,
            'email' => $clientDto->email,
            'company' => $clientDto->company,
            'phone' => $clientDto->phone,
            'street' => $clientDto->street,
            'city' => $clientDto->city,
            'zip_code' => $clientDto->zipCode,
            'country' => $clientDto->country,
            'client_type' => $clientDto->clientType,
            'siret' => $clientDto->siret,
            'tva_intra' => $clientDto->tvaIntra,
        ]);
    }

    public function update(Client $client, ClientDto $clientDto): Client
    {
        $client->update([
            'contact_name' => $clientDto->contactName,
            'private_name' => $clientDto->privateName,
            'email' => $clientDto->email,
            'company' => $clientDto->company,
            'phone' => $clientDto->phone,
            'street' => $clientDto->street,
            'city' => $clientDto->city,
            'zip_code' => $clientDto->zipCode,
            'country' => $clientDto->country,
            'client_type' => $clientDto->clientType,
            'siret' => $clientDto->siret,
            'tva_intra' => $clientDto->tvaIntra,
        ]);

        return $client->refresh();
    }
}
