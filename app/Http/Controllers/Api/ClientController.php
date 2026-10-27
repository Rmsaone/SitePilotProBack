<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Dto\ClientDto;
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
                $clientIndexRequest->filled('search') ? $clientIndexRequest->string('search')->toString() : null,
                $clientIndexRequest->filled('per_page') ? $clientIndexRequest->integer('per_page') : 20,
            )
        )->response();
    }

    public function show(Client $client): JsonResponse
    {
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

        return IndexClientResource::make($createdClient)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        Client $client,
        ClientRequest $clientRequest
    ): JsonResponse {
        $clientDto = ClientDto::fromArray(
            $clientRequest->safe()->toArray()
        );

        /** @var Client $updatedClient */
        $updatedClient = DB::transaction(
            fn () => $this->clientService->update(
                $client,
                $clientDto
            )
        );

        return IndexClientResource::make($updatedClient)
            ->response();
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json(
            null,
            Response::HTTP_NO_CONTENT
        );
    }
}
