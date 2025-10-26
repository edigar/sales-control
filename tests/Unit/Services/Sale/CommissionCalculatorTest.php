<?php

namespace Tests\Unit\Services\Sale;

use App\Services\Sale\CommissionCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CommissionCalculatorTest extends TestCase
{
    #[DataProvider('commissionProvider')]
    public function test_calculates_commission_correctly(float $rate, float $amount, float $expected): void
    {
        $calculator = new CommissionCalculator($rate);

        $this->assertEqualsWithDelta($expected, $calculator->calculate($amount), 0.001);
    }

    public static function commissionProvider(): array
    {
        return [
            // Basic cases
            '8.5% on 100' => [8.5, 100.0, 8.5],
            '8.5% on 123.45' => [8.5, 123.45, 10.49],
            '5% on 200' => [5, 200.0, 10.0],
            '12.34% on 99.99' => [12.34, 99.99, 12.34],
            
            // Extreme cases
            'minimum rate (0%)' => [0.0, 1000.0, 0.0],
            'maximum rate (100%)' => [100.0, 10.0, 10.0],
            'small amount' => [8.5, 0.01, 0.0],
            'large amount' => [8.5, 100000.00, 8500.0],
            
            // Rounding
            'rounds down' => [8.5, 100.11, 8.51],
            'rounds up' => [8.5, 100.17, 8.51],
            'rounds half up' => [8.5, 100.176, 8.51],
            'exact two decimals' => [10.0, 50.50, 5.05],
            'rounding with 3 decimals' => [8.5, 123.456, 10.49],
        ];
    }

    #[DataProvider('exceptionProvider')]
    public function test_throws_exception_for_invalid_inputs(
        float $rate,
        float $amount,
        string $expectedMessage
    ): void {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage($expectedMessage);

        $calculator = new CommissionCalculator($rate);
        $calculator->calculate($amount);
    }

    public static function exceptionProvider(): array
    {
        return [
            'negative amount' => [
                8.5,
                -100.0,
                'Invalid sale amount: expected a positive numeric value, got: -100'
            ],
            'zero amount' => [
                8.5,
                0.0,
                'Invalid sale amount: expected a positive numeric value, got: 0'
            ],
            'negative rate' => [
                -5.0,
                100.0,
                'Rate must be between 0 and 100, got: -5'
            ],
            'rate greater than 100' => [
                150.0,
                100.0,
                'Rate must be between 0 and 100, got: 150'
            ],
        ];
    }
}
