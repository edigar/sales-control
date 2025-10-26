<?php

namespace App\Services\Sale;

use App\Services\Sale\Contracts\CommissionCalculatorInterface;

class CommissionCalculator implements CommissionCalculatorInterface
{
    
    /**
     * Create a new commission calculator.
     *
     * @param float $rate
     */
    public function __construct(private readonly float $rate)
    {
    }

    /**
     * Calculate the commission for a given amount.
     *
     * @param float $amount
     * @return float
     * @throws \DomainException
     */
    public function calculate(float $amount): float
    {
        $this->validateAmount($amount);
        $this->validateRate();

        $commission = $amount * ($this->rate / 100);

        return round($commission, 2);
    }

    /**
     * Validate the amount.
     *
     * @param float $amount
     * @return void
     * @throws \DomainException If the amount is not a positive numeric value.
     */
    private function validateAmount(float $amount): void
    {
        if (!is_numeric($amount) || $amount <= 0) {
            throw new \DomainException("Invalid sale amount: expected a positive numeric value, got: {$amount}");
        }
    }

    /**
     * Validate the rate.
     *
     * @return void
     * @throws \DomainException If the rate is not a positive numeric value or greater than 100.
     */
    private function validateRate(): void
    {
        if ($this->rate < 0 || $this->rate > 100) {
            throw new \DomainException("Rate must be between 0 and 100, got: {$this->rate}");
        }
    }
}
