<?php

namespace App\Dto;

readonly class SellerInputDTO
{
    public function __construct(
        public string $name,
        public string $email
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
