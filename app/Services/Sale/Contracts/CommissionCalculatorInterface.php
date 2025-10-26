<?php

namespace App\Services\Sale\Contracts;

interface CommissionCalculatorInterface
{
    /**
     * Calculate the commission for a given amount.
     *
     * @param float $amount
     * @return float
     * @throws \DomainException
     */
    public function calculate(float $amount): float;
}
