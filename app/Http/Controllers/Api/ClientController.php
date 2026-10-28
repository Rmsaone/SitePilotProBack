<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Dto\ClientDto;
use App\Dto\ClientFilterDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClientIndexRequest;
use App\Http\Requests\ClientRequest;
use App\Http\Resources\Client\IndexClientResource;
use App\Http\Resources\Client\ShowClientResource;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientService $clientService,
    ) {
    }

    public function index(ClientIndexRequest $clientIndexRequest): JsonResponse
    {
        return IndexClientResource::collection(
            $this->clientService->list(
                ClientFilterDto::fromArray($clientIndexRequest->safe()->toArray())
            )
        )->response();
    }

    public function show(int $client_id): JsonResponse
    {
        /** @var Client $client */
        $client = Client::query()->findOrFail($client_id);

        $client->loadMissing('user')->loadCount(ClientService::COUNTS);

        return ShowClientResource::make($client)->response();
    }

    public function store(ClientRequest $clientRequest): JsonResponse
    {
        $validated = $clientRequest->safe()->toArray();

        /** @var Client $createdClient */
        $createdClient = DB::transaction(
            fn () => $this->clientService->store(
                ClientDto::fromArray($validated)
            )
        );

        $createdClient->loadMissing('user')->loadCount(ClientService::COUNTS);

        return ShowClientResource::make($createdClient)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        int $client_id,
        ClientRequest $clientRequest
    ): JsonResponse {
        $validated = $clientRequest->safe()->toArray();

        /** @var Client $client */
        $client = Client::query()->findOrFail($client_id);

        $updatedClient = DB::transaction(
            fn () => $this->clientService->update(
                $client,
                ClientDto::fromArray($validated)
            )
        );

        $updatedClient->loadMissing('user')->loadCount(ClientService::COUNTS);

        return ShowClientResource::make($updatedClient)->response();
    }

    public function destroy(int $client_id): JsonResponse
    {
        /** @var Client $client */
        $client = Client::query()->findOrFail($client_id);

        $this->clientService->destroy($client);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
