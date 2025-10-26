<?php

namespace App\Dto;

readonly class DailySellerSalesReportDTO
{
    public function __construct(
        public int $sellerId,
        public string $sellerName,
        public string $sellerEmail,
        public int $totalSales,
        public float $totalAmount,
        public float $totalCommission,
        public string $reportDate,
    ) {
    }

    public function toArray(): array
    {
        return [
            'seller_id' => $this->sellerId,
            'seller_name' => $this->sellerName,
            'seller_email' => $this->sellerEmail,
            'total_sales' => $this->totalSales,
            'total_amount' => $this->totalAmount,
            'total_commission' => $this->totalCommission,
            'report_date' => $this->reportDate,
        ];
    }
}
