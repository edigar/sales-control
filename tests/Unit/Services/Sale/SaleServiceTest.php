<?php

namespace Tests\Unit\Services\Sale;

use App\Dto\SaleInputDTO;
use App\Models\Sale;
use App\Repositories\Sale\Contracts\SaleRepositoryInterface;
use App\Services\Sale\Contracts\CommissionCalculatorInterface;
use App\Services\Sale\SaleService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    /** @var SaleRepositoryInterface&\Mockery\MockInterface */
    private SaleRepositoryInterface $repositoryMock;
    
    /** @var CommissionCalculatorInterface&\Mockery\MockInterface */
    private CommissionCalculatorInterface $commissionCalculatorMock;
    
    private SaleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(SaleRepositoryInterface::class);
        $this->commissionCalculatorMock = Mockery::mock(CommissionCalculatorInterface::class);
        $this->service = new SaleService(
            $this->repositoryMock,
            $this->commissionCalculatorMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== Helper Methods ====================

    private function mockDbTransaction(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn($callback) => $callback());
    }

    private function mockCommissionCalculation(float $amount, float $commission): void
    {
        $this->commissionCalculatorMock
            ->shouldReceive('calculate')
            ->once()
            ->with($amount)
            ->andReturn($commission);
    }

    private function createSaleInputDTO(
        int $sellerId = 1,
        float $amount = 1000.00,
        string $date = '2025-10-26'
    ): SaleInputDTO {
        return new SaleInputDTO(
            seller_id: $sellerId,
            amount: $amount,
            commission: 0,
            date: $date
        );
    }

    private function createSaleModel(
        int $id,
        int $sellerId,
        float $amount,
        float $commission,
        string $date
    ): Sale {
        $sale = new Sale();
        $sale->id = $id;
        $sale->seller_id = $sellerId;
        $sale->amount = $amount;
        $sale->commission = $commission;
        $sale->date = $date;
        return $sale;
    }

    private function createPaginator(array $items, int $total, int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: collect($items),
            total: $total,
            perPage: $perPage,
            currentPage: 1
        );
    }

    public function test_create_sale_successfully(): void
    {
        $saleInputDTO = $this->createSaleInputDTO();
        $calculatedCommission = 85.00;
        $expectedSale = $this->createSaleModel(1, 1, 1000.00, $calculatedCommission, '2025-10-26');

        $this->mockCommissionCalculation(1000.00, $calculatedCommission);
        $this->mockDbTransaction();

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) use ($calculatedCommission) {
                return $arg instanceof SaleInputDTO 
                    && $arg->seller_id === 1
                    && $arg->amount === 1000.00
                    && $arg->commission === $calculatedCommission
                    && $arg->date === '2025-10-26';
            }))
            ->andReturn($expectedSale);

        $result = $this->service->createSale($saleInputDTO);

        $this->assertInstanceOf(Sale::class, $result);
        $this->assertEquals($expectedSale->id, $result->id);
        $this->assertEquals($expectedSale->seller_id, $result->seller_id);
        $this->assertEquals($expectedSale->amount, $result->amount);
        $this->assertEquals($calculatedCommission, $result->commission);
        $this->assertEquals($expectedSale->date, $result->date);
    }

    public function test_create_sale_calculates_commission_before_persisting(): void
    {
        $saleInputDTO = $this->createSaleInputDTO(sellerId: 2, amount: 500.00);
        $calculatedCommission = 42.50;
        $expectedSale = $this->createSaleModel(2, 2, 500.00, $calculatedCommission, '2025-10-26');

        $this->mockCommissionCalculation(500.00, $calculatedCommission);
        $this->mockDbTransaction();

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->andReturn($expectedSale);

        $result = $this->service->createSale($saleInputDTO);

        $this->assertEquals($calculatedCommission, $result->commission);
    }

    public function test_create_sale_with_transaction_rollback_on_repository_error(): void
    {
        $saleInputDTO = $this->createSaleInputDTO();

        $this->mockCommissionCalculation(1000.00, 85.00);
        $this->mockDbTransaction();

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->createSale($saleInputDTO);
    }

    public function test_create_sale_with_commission_calculator_throwing_exception(): void
    {
        $saleInputDTO = $this->createSaleInputDTO(amount: -100.00);

        $this->commissionCalculatorMock
            ->shouldReceive('calculate')
            ->once()
            ->with(-100.00)
            ->andThrow(new \DomainException('Invalid sale amount: expected a positive numeric value, got: -100'));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid sale amount: expected a positive numeric value, got: -100');

        $this->service->createSale($saleInputDTO);
    }

    public function test_get_all_sales_successfully(): void
    {
        $perPage = 15;
        $sales = [
            (object) ['id' => 1, 'seller_id' => 1, 'amount' => 1000.00, 'commission' => 85.00, 'date' => '2025-10-26'],
            (object) ['id' => 2, 'seller_id' => 2, 'amount' => 500.00, 'commission' => 42.50, 'date' => '2025-10-25'],
            (object) ['id' => 3, 'seller_id' => 1, 'amount' => 750.00, 'commission' => 63.75, 'date' => '2025-10-24'],
        ];
        $expectedPaginator = $this->createPaginator($sales, 3, $perPage);

        $this->mockDbTransaction();
        $this->repositoryMock
            ->shouldReceive('getAll')
            ->once()
            ->with($perPage)
            ->andReturn($expectedPaginator);

        $result = $this->service->getAllSales($perPage);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->total());
        $this->assertEquals($perPage, $result->perPage());
        $this->assertCount(3, $result->items());
    }

    public function test_get_all_sales_returns_empty_paginator_when_no_sales(): void
    {
        $perPage = 10;
        $emptyPaginator = $this->createPaginator([], 0, $perPage);

        $this->mockDbTransaction();
        $this->repositoryMock
            ->shouldReceive('getAll')
            ->once()
            ->with($perPage)
            ->andReturn($emptyPaginator);

        $result = $this->service->getAllSales($perPage);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
        $this->assertCount(0, $result->items());
    }

    public function test_get_all_sales_with_custom_per_page(): void
    {
        $perPage = 5;
        $sales = [(object) ['id' => 1, 'seller_id' => 1, 'amount' => 1000.00, 'commission' => 85.00, 'date' => '2025-10-26']];
        $expectedPaginator = $this->createPaginator($sales, 1, $perPage);

        $this->mockDbTransaction();
        $this->repositoryMock
            ->shouldReceive('getAll')
            ->once()
            ->with($perPage)
            ->andReturn($expectedPaginator);

        $result = $this->service->getAllSales($perPage);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals($perPage, $result->perPage());
    }

    public function test_get_all_sales_with_transaction_rollback_on_error(): void
    {
        $perPage = 15;

        $this->mockDbTransaction();
        $this->repositoryMock
            ->shouldReceive('getAll')
            ->once()
            ->with($perPage)
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->getAllSales($perPage);
    }

    public function test_get_sales_by_seller_successfully(): void
    {
        $sellerId = 1;
        $perPage = 10;
        $sales = [
            (object) ['id' => 1, 'seller_id' => 1, 'amount' => 1000.00, 'commission' => 85.00, 'date' => '2025-10-26'],
            (object) ['id' => 3, 'seller_id' => 1, 'amount' => 750.00, 'commission' => 63.75, 'date' => '2025-10-24'],
        ];
        $expectedPaginator = $this->createPaginator($sales, 2, $perPage);

        $this->mockDbTransaction();
        $this->repositoryMock
            ->shouldReceive('getBySeller')
            ->once()
            ->with($sellerId, $perPage)
            ->andReturn($expectedPaginator);

        $result = $this->service->getSalesBySeller($sellerId, $perPage);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->total());
        $this->assertEquals($perPage, $result->perPage());
        $this->assertCount(2, $result->items());
    }

    public function test_get_sales_by_seller_returns_empty_paginator_when_no_sales(): void
    {
        $sellerId = 99;
        $perPage = 10;
        $emptyPaginator = $this->createPaginator([], 0, $perPage);

        $this->mockDbTransaction();
        $this->repositoryMock
            ->shouldReceive('getBySeller')
            ->once()
            ->with($sellerId, $perPage)
            ->andReturn($emptyPaginator);

        $result = $this->service->getSalesBySeller($sellerId, $perPage);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
        $this->assertCount(0, $result->items());
    }

    public function test_get_sales_by_seller_with_custom_per_page(): void
    {
        $sellerId = 2;
        $perPage = 20;
        $sales = [(object) ['id' => 2, 'seller_id' => 2, 'amount' => 500.00, 'commission' => 42.50, 'date' => '2025-10-25']];
        $expectedPaginator = $this->createPaginator($sales, 1, $perPage);

        $this->mockDbTransaction();
        $this->repositoryMock
            ->shouldReceive('getBySeller')
            ->once()
            ->with($sellerId, $perPage)
            ->andReturn($expectedPaginator);

        $result = $this->service->getSalesBySeller($sellerId, $perPage);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals($perPage, $result->perPage());
    }

    public function test_get_sales_by_seller_with_transaction_rollback_on_error(): void
    {
        $sellerId = 1;
        $perPage = 10;

        $this->mockDbTransaction();
        $this->repositoryMock
            ->shouldReceive('getBySeller')
            ->once()
            ->with($sellerId, $perPage)
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->getSalesBySeller($sellerId, $perPage);
    }

    public function test_get_sales_by_seller_with_different_sellers(): void
    {
        $seller1Id = 1;
        $seller2Id = 2;
        $perPage = 10;

        $sales1 = [(object) ['id' => 1, 'seller_id' => 1, 'amount' => 1000.00, 'commission' => 85.00, 'date' => '2025-10-26']];
        $paginator1 = $this->createPaginator($sales1, 1, $perPage);

        $sales2 = [(object) ['id' => 2, 'seller_id' => 2, 'amount' => 500.00, 'commission' => 42.50, 'date' => '2025-10-25']];
        $paginator2 = $this->createPaginator($sales2, 1, $perPage);

        DB::shouldReceive('transaction')
            ->twice()
            ->andReturnUsing(fn($callback) => $callback());

        $this->repositoryMock
            ->shouldReceive('getBySeller')
            ->once()
            ->with($seller1Id, $perPage)
            ->andReturn($paginator1);

        $this->repositoryMock
            ->shouldReceive('getBySeller')
            ->once()
            ->with($seller2Id, $perPage)
            ->andReturn($paginator2);

        $result1 = $this->service->getSalesBySeller($seller1Id, $perPage);
        $result2 = $this->service->getSalesBySeller($seller2Id, $perPage);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result1);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result2);
        $this->assertCount(1, $result1->items());
        $this->assertCount(1, $result2->items());
        $this->assertEquals(1, $result1->items()[0]->seller_id);
        $this->assertEquals(2, $result2->items()[0]->seller_id);
    }
}
