<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class RegisterDto
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
        public string $password,
        public ?string $affiliationCode,
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
            password: (string) $data['password'],
            affiliationCode: isset($data['affiliation_code'])
                ? mb_strtoupper((string) $data['affiliation_code'])
                : null,
        );
    }
}
