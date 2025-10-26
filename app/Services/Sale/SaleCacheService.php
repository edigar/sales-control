<?php

namespace App\Services\Sale;

use App\Dto\SaleInputDTO;
use App\Models\Sale;
use App\Services\Sale\Contracts\SaleServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Sale Cache Service - Proxy Pattern Implementation
 * 
 * This service acts as a proxy for SaleService, adding Redis caching
 * capabilities for the getAllSales method to improve performance.
 */
class SaleCacheService implements SaleServiceInterface
{
    /**
     * Cache key prefix for sales data
     */
    private const CACHE_KEY_PREFIX = 'sales:all';

    /**
     * Cache TTL in seconds (10 min)
     */
    private const CACHE_TTL = 600;

    /**
     * Create a new sale cache service.
     *
     * @param SaleServiceInterface $saleService The decorated sale service
     */
    public function __construct(
        private readonly SaleServiceInterface $saleService
    ) {}

    /**
     * Create a new sale and invalidate cache.
     *
     * @param SaleInputDTO $saleInputDTO
     * @return Sale
     * @throws \DomainException
     */
    public function createSale(SaleInputDTO $saleInputDTO): Sale
    {
        return $this->saleService->createSale($saleInputDTO);
    }

    /**
     * Get all sales with Redis caching.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws \DomainException
     */
    public function getAllSales(int $perPage): LengthAwarePaginator
    {
        $cacheKey = $this->getCacheKey($perPage);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($perPage) {
            return $this->saleService->getAllSales($perPage);
        });
    }

    /**
     * Get sales by seller (no caching - delegates to service).
     *
     * @param int $sellerId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws \DomainException
     */
    public function getSalesBySeller(int $sellerId, int $perPage): LengthAwarePaginator
    {
        return $this->saleService->getSalesBySeller($sellerId, $perPage);
    }

    /**
     * Generate cache key for getAllSales method.
     *
     * @param int $perPage
     * @return string
     */
    private function getCacheKey(int $perPage): string
    {
        return sprintf('%s:page_size:%d', self::CACHE_KEY_PREFIX, $perPage);
    }
}

