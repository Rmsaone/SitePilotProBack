<?php

declare(strict_types=1);

namespace App\Actions\Client;

use App\Dto\ClientDto;
use App\Models\Client;

final class CreateClientAction
{
    public function __invoke(
        ClientDto $clientDto,
    ): Client {
        return Client::query()->create($clientDto->attributes());
    }
}
