<?php

namespace App\Services\Sale;

use App\Dto\SaleInputDTO;
use App\Models\Sale;
use App\Repositories\Sale\Contracts\SaleRepositoryInterface;
use App\Services\Sale\Contracts\CommissionCalculatorInterface;
use App\Services\Sale\Contracts\SaleServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SaleService implements SaleServiceInterface
{
    
    /**
     * Create a new sale service.
     *
     * @param SaleRepositoryInterface $saleRepository
     * @param CommissionCalculatorInterface $commissionCalculator
     */
    public function __construct(
        private readonly SaleRepositoryInterface       $saleRepository,
        private readonly CommissionCalculatorInterface $commissionCalculator
    ) {}

    /**
     * Create a new sale.
     *
     * @param SaleInputDTO $saleInputDTO
     * @return Sale
     * @throws \DomainException
     */
    public function createSale(SaleInputDTO $saleInputDTO): Sale
    {
        $saleInputDTO->commission = $this->commissionCalculator->calculate($saleInputDTO->amount);

        return DB::transaction(function () use ($saleInputDTO) {
            return $this->saleRepository->create($saleInputDTO);
        });
    }

    /**
     * Get all sales.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws \DomainException
     */
    public function getAllSales(int $perPage): LengthAwarePaginator
    {
        return DB::transaction(function () use ($perPage) {
            return $this->saleRepository->getAll($perPage);
        });
    }

    /**
     * Get sales by seller.
     *
     * @param int $sellerId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws \DomainException
     */
    public function getSalesBySeller(int $sellerId, int $perPage): LengthAwarePaginator
    {
        return DB::transaction(function () use ($sellerId, $perPage) {
            return $this->saleRepository->getBySeller($sellerId, $perPage);
        });
    }
}
