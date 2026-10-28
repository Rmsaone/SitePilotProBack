<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enums\ClientType;

final readonly class ClientDto
{
    public function __construct(
        public string $contactName,
        public ?string $privateName,
        public string $email,
        public ?string $company,
        public string $phone,
        public string $street,
        public string $city,
        public int $zipCode,
        public string $country,
        public ClientType $clientType,
        public ?string $siret,
        public ?string $tvaIntra,
        public int $userId,
    ) {
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $clientType = $data['client_type'] instanceof ClientType
            ? $data['client_type']
            : ClientType::from((string) $data['client_type']);
        $isPro = $clientType === ClientType::Pro;

        return new self(
            contactName: (string) $data['contact_name'],
            privateName: isset($data['private_name']) ? (string) $data['private_name'] : null,
            email: (string) $data['email'],
            company: isset($data['company']) ? (string) $data['company'] : null,
            phone: (string) $data['phone'],
            street: (string) $data['street'],
            city: (string) $data['city'],
            zipCode: (int) $data['zip_code'],
            country: (string) $data['country'],
            clientType: $clientType,
            // A private client never keeps company identifiers.
            siret: $isPro && isset($data['siret']) ? (string) $data['siret'] : null,
            tvaIntra: $isPro && isset($data['tva_intra']) ? (string) $data['tva_intra'] : null,
            userId: (int) $data['user_id'],
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function attributes(): array
    {
        return [
            'user_id' => $this->userId,
            'contact_name' => $this->contactName,
            'private_name' => $this->privateName,
            'email' => $this->email,
            'company' => $this->company,
            'phone' => $this->phone,
            'street' => $this->street,
            'city' => $this->city,
            'zip_code' => $this->zipCode,
            'country' => $this->country,
            'clientType' => $this->clientType,
            'siret' => $this->siret,
            'tva_intra' => $this->tvaIntra,
        ];
    }
}
