<?php

namespace Tests\Unit\Services\Sale;

use App\Dto\SaleInputDTO;
use App\Models\Sale;
use App\Services\Sale\Contracts\SaleServiceInterface;
use App\Services\Sale\SaleCacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SaleCacheServiceTest extends TestCase
{
    private SaleServiceInterface|MockInterface $baseSaleService;
    private SaleCacheService $saleCacheService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseSaleService = Mockery::mock(SaleServiceInterface::class);
        
        $this->saleCacheService = new SaleCacheService($this->baseSaleService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Cache::forget('sales:all:page_size:10');
        Cache::forget('sales:all:page_size:15');
        Cache::forget('sales:all:page_size:20');
        parent::tearDown();
    }

    public function test_get_all_sales_returns_cached_data_when_available(): void
    {
        $perPage = 15;
        $expectedData = Mockery::mock(LengthAwarePaginator::class);
        
        Cache::put('sales:all:page_size:15', $expectedData, 600);
        
        $this->baseSaleService->shouldNotReceive('getAllSales');

        $result = $this->saleCacheService->getAllSales($perPage);

        $this->assertSame($expectedData, $result);
    }

    public function test_get_all_sales_fetches_and_caches_when_cache_is_empty(): void
    {
        $perPage = 15;
        $expectedData = Mockery::mock(LengthAwarePaginator::class);
        
        Cache::forget('sales:all:page_size:15');
        
        $this->baseSaleService
            ->shouldReceive('getAllSales')
            ->once()
            ->with($perPage)
            ->andReturn($expectedData);

        $result = $this->saleCacheService->getAllSales($perPage);

        $this->assertSame($expectedData, $result);
        
        $cachedData = Cache::get('sales:all:page_size:15');
        $this->assertSame($expectedData, $cachedData);
    }

    public function test_get_all_sales_uses_different_cache_keys_for_different_per_page_values(): void
    {
        $perPage1 = 10;
        $perPage2 = 20;
        $data1 = Mockery::mock(LengthAwarePaginator::class);
        $data2 = Mockery::mock(LengthAwarePaginator::class);
        
        $this->baseSaleService
            ->shouldReceive('getAllSales')
            ->once()
            ->with($perPage1)
            ->andReturn($data1);
        
        $this->baseSaleService
            ->shouldReceive('getAllSales')
            ->once()
            ->with($perPage2)
            ->andReturn($data2);

        $result1 = $this->saleCacheService->getAllSales($perPage1);
        $result2 = $this->saleCacheService->getAllSales($perPage2);

        $this->assertSame($data1, $result1);
        $this->assertSame($data2, $result2);
        
        $this->assertSame($data1, Cache::get('sales:all:page_size:10'));
        $this->assertSame($data2, Cache::get('sales:all:page_size:20'));
    }

    public function test_create_sale_delegates_to_base_service(): void
    {
        $saleInputDTO = new SaleInputDTO(
            seller_id: 1,
            amount: 1000.00,
            commission: 85.00,
            date: '2024-01-01'
        );
        
        $sale = new Sale();
        $sale->id = 1;
        
        $this->baseSaleService
            ->shouldReceive('createSale')
            ->once()
            ->with($saleInputDTO)
            ->andReturn($sale);

        $result = $this->saleCacheService->createSale($saleInputDTO);

        $this->assertSame($sale, $result);
    }

    public function test_get_sales_by_seller_delegates_to_base_service(): void
    {
        $sellerId = 1;
        $perPage = 15;
        $expectedData = Mockery::mock(LengthAwarePaginator::class);
        
        $this->baseSaleService
            ->shouldReceive('getSalesBySeller')
            ->once()
            ->with($sellerId, $perPage)
            ->andReturn($expectedData);

        $result = $this->saleCacheService->getSalesBySeller($sellerId, $perPage);

        $this->assertSame($expectedData, $result);
    }
}

