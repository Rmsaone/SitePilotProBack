<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Dto\RegisterDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\User\ShowUserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    public function register(RegisterRequest $registerRequest): JsonResponse
    {
        $validated = $registerRequest->safe()->toArray();

        /** @var User $user */
        $user = DB::transaction(
            fn () => $this->authService->register(
                RegisterDto::fromArray($validated)
            )
        );

        $this->authService->login($registerRequest, $user);

        return response()->json([
            'user' => ShowUserResource::make($user),
        ], Response::HTTP_CREATED);
    }

    public function login(LoginRequest $loginRequest): JsonResponse
    {
        $validated = $loginRequest->safe()->toArray();

        $user = $this->authService->attempt(
            (string) $validated['email'],
            (string) $validated['password'],
        );

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $this->authService->login($loginRequest, $user);

        return response()->json([
            'user' => ShowUserResource::make($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return ShowUserResource::make($user)->response();
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function logoutEverywhere(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authService->logoutEverywhere($request, $user);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
