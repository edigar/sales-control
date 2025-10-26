<?php

namespace App\Repositories\Sale\Contracts;

use App\Models\Sale;
use App\Dto\SaleInputDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SaleRepositoryInterface
{
    /**
     * Create a new sale.
     *
     * @param SaleInputDTO $saleInputDTO
     * @return Sale
     */
    public function create(SaleInputDTO $saleInputDTO): Sale;

    /**
     * Get sales by seller.
     *
     * @param int $sellerId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getBySeller(int $sellerId, int $perPage): LengthAwarePaginator;

    /**
     * Get all sales.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAll(int $perPage): LengthAwarePaginator;

    /**
     * Get daily sales report for all sellers with sales on the specified date.
     *
     * @param string $date
     * @return Collection
     */
    public function getDailySalesReportBySeller(string $date): \Illuminate\Support\Collection;

    /**
     * Get daily sales report on the specified date.
     *
     * @param string $date
     * @return object
     */
    public function getDailySalesReport(string $date): object;
}
