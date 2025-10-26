<?php

namespace App\Dto;

class SaleInputDTO
{
    public function __construct(
        public readonly int $seller_id,
        public readonly float $amount,
        public float $commission,
        public readonly string $date,
    ) {}

    public function toArray(): array
    {
        return [
            'seller_id' => $this->seller_id,
            'amount' => $this->amount,
            'commission' => $this->commission,
            'date' => $this->date,
        ];
    }
}
