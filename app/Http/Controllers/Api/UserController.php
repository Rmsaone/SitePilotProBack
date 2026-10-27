<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\IndexUserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    public function index(): JsonResponse
    {
        return IndexUserResource::collection(
            $this->userService->list()
        )->response();
    }
}
