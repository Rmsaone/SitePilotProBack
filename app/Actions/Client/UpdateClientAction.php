<?php

declare(strict_types=1);

namespace App\Actions\Client;

use App\Dto\ClientDto;
use App\Models\Client;

final class UpdateClientAction
{
    public function __invoke(
        Client $client,
        ClientDto $clientDto,
    ): Client {
        $client->update($clientDto->attributes());

        return $client->refresh();
    }
}
