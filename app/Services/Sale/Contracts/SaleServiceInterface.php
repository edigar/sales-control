<?php

namespace App\Services\Sale\Contracts;

use App\Dto\SaleInputDTO;
use App\Models\Sale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SaleServiceInterface
{
    
    /**
     * Create a new sale.
     *
     * @param SaleInputDTO $saleInputDTO
     * @return Sale
     */
    public function createSale(SaleInputDTO $saleInputDTO): Sale;

    /**
     * Get all sales.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllSales(int $perPage): LengthAwarePaginator;

    /**
     * Get sales by seller.
     *
     * @param int $sellerId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getSalesBySeller(int $sellerId, int $perPage): LengthAwarePaginator;
}
