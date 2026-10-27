<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enums\UserRole;

final readonly class ProfileDto
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
        public ?UserRole $userRole,
    ) {
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            firstName: (string) $data['first_name'],
            lastName: (string) $data['last_name'],
            email: (string) $data['email'],
            phone: (string) $data['phone'],
            userRole: isset($data['user_role'])
                ? UserRole::from($data['user_role'])
                : null,
        );
    }
}
