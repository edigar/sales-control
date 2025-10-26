<?php

namespace App\Services\Sale\Contracts;

use App\Dto\DailySalesReportDTO;
use App\Dto\DailySellerSalesReportDTO;
use Illuminate\Support\Collection;

interface SalesReportServiceInterface
{
    /**
     * Generates daily sales report.
     * 
     * @param ?string|null $date Date in Y-m-d format. If null, uses the current date.
     * @return DailySalesReportDTO
     * @throws \RuntimeException
     */
    public function generateDailySalesReport(?string $date = null): DailySalesReportDTO;

    /**
     * Generates daily sales reports for all sellers that had sales on the specified date.
     *
     * @param ?string|null $date Date in Y-m-d format. If null, uses the current date.
     * @return Collection<DailySellerSalesReportDTO>
     */
    public function generateDailySalesReportBySeller(?string $date = null): Collection;
}

