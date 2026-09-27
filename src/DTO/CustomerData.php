<?php

namespace Devaspid\Safi\DTO;

class CustomerData
{
    public function __construct(
        public string|int $sourceCustomerId,
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $code = null
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'source_customer_id' => $this->sourceCustomerId,
            'code' => $this->code ?? ('CUST-' . $this->sourceCustomerId),
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
        ], fn ($val) => ! is_null($val));
    }
}
