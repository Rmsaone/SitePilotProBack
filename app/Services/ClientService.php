<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Client\CreateClientAction;
use App\Actions\Client\DeleteClientAction;
use App\Actions\Client\UpdateClientAction;
use App\Dto\ClientDto;
use App\Dto\ClientFilterDto;
use App\Enums\ClientType;
use App\Models\Client;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClientService
{
    public const array COUNTS = ['worksites', 'quotes', 'invoicesClient'];

    public function list(ClientFilterDto $clientFilterDto): LengthAwarePaginator
    {
        return Client::query()
            ->with('user')
            ->withCount(self::COUNTS)
            ->when($clientFilterDto->search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereLike('company', "%{$search}%")
                        ->orWhereLike('contact_name', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%")
                        ->orWhereLike('city', "%{$search}%");
                });
            })
            ->when($clientFilterDto->clientType, fn (Builder $query, ClientType $type) => $query->where('clientType', $type))
            ->orderBy($clientFilterDto->sort, $clientFilterDto->direction)
            ->orderByDesc('id')
            ->paginate($clientFilterDto->perPage);
    }

    public function store(ClientDto $clientDto): Client
    {
        /** @var Client $client */
        $client = app()->call(CreateClientAction::class, [
            'clientDto' => $clientDto,
        ]);

        return $client->refresh();
    }

    public function update(Client $client, ClientDto $clientDto): Client
    {
        /** @var Client $updatedClient */
        $updatedClient = app()->call(UpdateClientAction::class, [
            'client' => $client,
            'clientDto' => $clientDto,
        ]);

        return $updatedClient;
    }

    public function destroy(Client $client): void
    {
        app()->call(DeleteClientAction::class, [
            'client' => $client,
        ]);
    }
}
