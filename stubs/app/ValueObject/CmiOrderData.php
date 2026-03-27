<?php

namespace App\ValueObject;

final class CmiOrderData
{
    public function __construct(
        public readonly string $orderId,
        public readonly float $amount,
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $address,
        public readonly string $city,
        public readonly string $state,
        public readonly string $postalCode,
        public readonly string $countryCode = '504',
        public readonly string $company = '',
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
