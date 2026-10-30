<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Dto\ProfileDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\User\ShowUserResource;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {
    }

    public function update(UpdateProfileRequest $updateProfileRequest): JsonResponse
    {
        $validated = $updateProfileRequest->safe()->toArray();

        /** @var User $user */
        $user = $updateProfileRequest->user();

        /** @var User $updatedUser */
        $updatedUser = DB::transaction(
            fn () => $this->profileService->update(
                $user,
                ProfileDto::fromArray($validated)
            )
        );

        return ShowUserResource::make($updatedUser)->response();
    }

    public function updatePassword(UpdatePasswordRequest $updatePasswordRequest): JsonResponse
    {
        $validated = $updatePasswordRequest->safe()->toArray();

        /** @var User $user */
        $user = $updatePasswordRequest->user();

        /** @var User $updatedUser */
        $updatedUser = DB::transaction(
            fn () => $this->profileService->updatePassword(
                $user,
                (string) $validated['password']
            )
        );

        return ShowUserResource::make($updatedUser)->response();
    }
}
