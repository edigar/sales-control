<?php

namespace App\Services\Sale;

use App\Dto\DailySellerSalesReportDTO;
use App\Dto\DailySalesReportDTO;
use App\Repositories\Sale\Contracts\SaleRepositoryInterface;
use App\Services\Sale\Contracts\SalesReportServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesReportService implements SalesReportServiceInterface
{
    /**
     * Create a new sales report service.
     *
     * @param SaleRepositoryInterface $saleRepository
     */
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository
    ) {}

    /**
     * Generates daily sales reports for all sellers that had sales on the specified date.
     * 
     * @param string|null $date
     * @return Collection<DailySellerSalesReportDTO>
     */
    public function generateDailySalesReportBySeller(?string $date = null): Collection
    {
        $reportDate = $date ?? now()->toDateString();

        return DB::transaction(function () use ($reportDate) {
            $sellersWithSales = $this->saleRepository->getDailySalesReportBySeller($reportDate);

            return $sellersWithSales->map(function ($seller) use ($reportDate) {
                return new DailySellerSalesReportDTO(
                    sellerId: $seller->seller_id,
                    sellerName: $seller->seller_name,
                    sellerEmail: $seller->seller_email,
                    totalSales: (int) $seller->total_sales,
                    totalAmount: (float) $seller->total_amount,
                    totalCommission: (float) $seller->total_commission,
                    reportDate: $reportDate,
                );
            });
        });
    }

    /**
     * Generates daily sales report.
     * 
     * @param ?string|null $date Date in Y-m-d format. If null, uses the current date.
     * @return DailySalesReportDTO
     * @throws \RuntimeException
     */
    public function generateDailySalesReport(?string $date = null): DailySalesReportDTO
    {
        $reportDate = $date ?? now()->toDateString();

        return DB::transaction(function () use ($reportDate) {
            $salesData = $this->saleRepository->getDailySalesReport($reportDate);

            return new DailySalesReportDTO(
                userName: "Admin",
                totalSales: (int) $salesData->total_sales,
                totalAmount: (float) $salesData->total_amount,
                totalCommission: (float) $salesData->total_commission,
                reportDate: $reportDate,
            );
        });
    }
}

