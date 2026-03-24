<?php

namespace App\ValueObject;

final readonly class CmiOrderData
{
    public function __construct(
        public string $orderId,
        public float $amount,
        public string $name,
        public string $email,
        public string $phone,
        public string $address,
        public string $city,
        public string $state,
        public string $postalCode,
        public string $countryCode = '504',
        public string $company = '',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            orderId: $data['order_id'],
            amount: (float) $data['amount'],
            name: $data['name'] ?? '',
            email: $data['email'] ?? '',
            phone: $data['phone'] ?? '',
            address: $data['address'] ?? '',
            city: $data['city'] ?? '',
            state: $data['state'] ?? '',
            postalCode: $data['postal_code'] ?? '',
            countryCode: $data['country_code'] ?? '504',
            company: $data['company'] ?? '',
        );
    }
}
