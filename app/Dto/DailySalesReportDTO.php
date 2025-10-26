<?php

namespace App\Dto;

class DailySalesReportDTO
{
    public function __construct(
        public string $userName,
        public readonly int $totalSales,
        public readonly float $totalAmount,
        public readonly float $totalCommission,
        public readonly string $reportDate,
    ) {
    }

    public function toArray(): array
    {
        return [
            'user_name' => $this->userName,
            'total_sales' => $this->totalSales,
            'total_amount' => $this->totalAmount,
            'total_commission' => $this->totalCommission,
            'report_date' => $this->reportDate,
        ];
    }
}
