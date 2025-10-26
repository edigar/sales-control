<?php

namespace Tests\Unit\Services\Sale;

use App\Dto\DailySellerSalesReportDTO;
use App\Dto\DailySalesReportDTO;
use App\Repositories\Sale\Contracts\SaleRepositoryInterface;
use App\Services\Sale\SalesReportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class SalesReportServiceTest extends TestCase
{
    /** @var SaleRepositoryInterface&\Mockery\MockInterface */
    private SaleRepositoryInterface $repositoryMock;

    private SalesReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(SaleRepositoryInterface::class);
        $this->service = new SalesReportService($this->repositoryMock);
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

    private function createSellerSalesData(
        int $sellerId,
        string $sellerName,
        string $sellerEmail,
        int $totalSales,
        float $totalAmount,
        float $totalCommission
    ): object {
        return (object) [
            'seller_id' => $sellerId,
            'seller_name' => $sellerName,
            'seller_email' => $sellerEmail,
            'total_sales' => $totalSales,
            'total_amount' => $totalAmount,
            'total_commission' => $totalCommission,
        ];
    }

    private function createDailySalesData(
        int $totalSales,
        float $totalAmount,
        float $totalCommission
    ): object {
        return (object) [
            'total_sales' => $totalSales,
            'total_amount' => $totalAmount,
            'total_commission' => $totalCommission,
        ];
    }

    // ==================== generateDailySalesReportBySeller Tests ====================

    public function test_generate_daily_sales_report_by_seller_successfully(): void
    {
        $date = '2025-10-26';
        
        $sellerData = collect([
            $this->createSellerSalesData(1, 'John Doe', 'john@example.com', 5, 1000.00, 85.00),
            $this->createSellerSalesData(2, 'Jane Smith', 'jane@example.com', 3, 500.00, 42.50),
        ]);

        $this->mockDbTransaction();
        
        $this->repositoryMock
            ->shouldReceive('getDailySalesReportBySeller')
            ->once()
            ->with($date)
            ->andReturn($sellerData);

        $result = $this->service->generateDailySalesReportBySeller($date);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        
        $firstReport = $result->first();
        $this->assertInstanceOf(DailySellerSalesReportDTO::class, $firstReport);
        $this->assertEquals(1, $firstReport->sellerId);
        $this->assertEquals('John Doe', $firstReport->sellerName);
        $this->assertEquals('john@example.com', $firstReport->sellerEmail);
        $this->assertEquals(5, $firstReport->totalSales);
        $this->assertEquals(1000.00, $firstReport->totalAmount);
        $this->assertEquals(85.00, $firstReport->totalCommission);
        $this->assertEquals($date, $firstReport->reportDate);

        $secondReport = $result->last();
        $this->assertEquals(2, $secondReport->sellerId);
        $this->assertEquals('Jane Smith', $secondReport->sellerName);
    }

    public function test_generate_daily_sales_report_by_seller_with_no_date_uses_current_date(): void
    {
        $currentDate = now()->toDateString();
        
        $sellerData = collect([
            $this->createSellerSalesData(1, 'John Doe', 'john@example.com', 3, 750.00, 63.75),
        ]);

        $this->mockDbTransaction();
        
        $this->repositoryMock
            ->shouldReceive('getDailySalesReportBySeller')
            ->once()
            ->with($currentDate)
            ->andReturn($sellerData);

        $result = $this->service->generateDailySalesReportBySeller();

        $this->assertCount(1, $result);
        $this->assertEquals($currentDate, $result->first()->reportDate);
    }

    public function test_generate_daily_sales_report_by_seller_returns_empty_collection_when_no_sales(): void
    {
        $date = '2025-10-26';
        
        $this->mockDbTransaction();
        
        $this->repositoryMock
            ->shouldReceive('getDailySalesReportBySeller')
            ->once()
            ->with($date)
            ->andReturn(collect());

        $result = $this->service->generateDailySalesReportBySeller($date);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_generate_daily_sales_report_by_seller_converts_numeric_strings_to_proper_types(): void
    {
        $date = '2025-10-26';
        
        // Simula dados vindos do banco como strings
        $sellerData = collect([
            $this->createSellerSalesData(1, 'John Doe', 'john@example.com', '10', '2500.50', '212.54'),
        ]);

        $this->mockDbTransaction();
        
        $this->repositoryMock
            ->shouldReceive('getDailySalesReportBySeller')
            ->once()
            ->with($date)
            ->andReturn($sellerData);

        $result = $this->service->generateDailySalesReportBySeller($date);

        $report = $result->first();
        $this->assertIsInt($report->totalSales);
        $this->assertIsFloat($report->totalAmount);
        $this->assertIsFloat($report->totalCommission);
        $this->assertEquals(10, $report->totalSales);
        $this->assertEquals(2500.50, $report->totalAmount);
        $this->assertEquals(212.54, $report->totalCommission);
    }

    public function test_generate_daily_sales_report_by_seller_handles_multiple_sellers(): void
    {
        $date = '2025-10-26';
        
        $sellerData = collect([
            $this->createSellerSalesData(1, 'Seller A', 'a@example.com', 5, 1000.00, 85.00),
            $this->createSellerSalesData(2, 'Seller B', 'b@example.com', 3, 500.00, 42.50),
            $this->createSellerSalesData(3, 'Seller C', 'c@example.com', 7, 1500.00, 127.50),
        ]);

        $this->mockDbTransaction();
        
        $this->repositoryMock
            ->shouldReceive('getDailySalesReportBySeller')
            ->once()
            ->with($date)
            ->andReturn($sellerData);

        $result = $this->service->generateDailySalesReportBySeller($date);

        $this->assertCount(3, $result);
        $sellerIds = $result->pluck('sellerId')->toArray();
        $this->assertEquals([1, 2, 3], $sellerIds);
    }

    public function test_generate_daily_sales_report_by_seller_with_transaction_rollback_on_error(): void
    {
        $date = '2025-10-26';

        $this->mockDbTransaction();
        
        $this->repositoryMock
            ->shouldReceive('getDailySalesReportBySeller')
            ->once()
            ->with($date)
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->generateDailySalesReportBySeller($date);
    }

    // ==================== generateDailySalesReport Tests ====================

    public function test_generate_daily_sales_report_successfully(): void
    {
        $date = '2025-10-26';
        
        // O repositório retorna um objeto direto (não Collection)
        $salesData = $this->createDailySalesData(15, 5000.00, 425.00);

        $this->mockDbTransaction();
        
        $this->repositoryMock
            ->shouldReceive('getDailySalesReport')
            ->once()
            ->with($date)
            ->andReturn($salesData);

        $result = $this->service->generateDailySalesReport($date);

        $this->assertInstanceOf(DailySalesReportDTO::class, $result);
        $this->assertEquals('Admin', $result->userName);
        $this->assertEquals(15, $result->totalSales);
        $this->assertEquals(5000.00, $result->totalAmount);
        $this->assertEquals(425.00, $result->totalCommission);
        $this->assertEquals($date, $result->reportDate);
    }

    public function test_generate_daily_sales_report_with_no_date_uses_current_date(): void
    {
        $currentDate = now()->toDateString();
        
        $salesData = $this->createDailySalesData(10, 3000.00, 255.00);

        $this->mockDbTransaction();
        
        $this->repositoryMock
            ->shouldReceive('getDailySalesReport')
            ->once()
            ->with($currentDate)
            ->andReturn($salesData);

        $result = $this->service->generateDailySalesReport();

        $this->assertEquals($currentDate, $result->reportDate);
    }

    public function test_generate_daily_sales_report_with_no_sales(): void
    {
        $date = '2025-10-26';
        
        $salesData = $this->createDailySalesData(0, 0.00, 0.00);

        $this->mockDbTransaction();
        
        $this->repositoryMock
            ->shouldReceive('getDailySalesReport')
            ->once()
            ->with($date)
            ->andReturn($salesData);

        $result = $this->service->generateDailySalesReport($date);

        $this->assertInstanceOf(DailySalesReportDTO::class, $result);
        $this->assertEquals(0, $result->totalSales);
        $this->assertEquals(0.00, $result->totalAmount);
        $this->assertEquals(0.00, $result->totalCommission);
    }

    public function test_generate_daily_sales_report_converts_numeric_strings_to_proper_types(): void
    {
        $date = '2025-10-26';
        
        // Simula dados vindos do banco como strings
        $salesData = (object) [
            'total_sales' => '25',
            'total_amount' => '10000.50',
            'total_commission' => '850.04',
        ];

        $this->mockDbTransaction();
        
        $this->repositoryMock
            ->shouldReceive('getDailySalesReport')
            ->once()
            ->with($date)
            ->andReturn($salesData);

        $result = $this->service->generateDailySalesReport($date);

        $this->assertIsInt($result->totalSales);
        $this->assertIsFloat($result->totalAmount);
        $this->assertIsFloat($result->totalCommission);
        $this->assertEquals(25, $result->totalSales);
        $this->assertEquals(10000.50, $result->totalAmount);
        $this->assertEquals(850.04, $result->totalCommission);
    }

    public function test_generate_daily_sales_report_always_sets_admin_as_user_name(): void
    {
        $date = '2025-10-26';
        
        $salesData = $this->createDailySalesData(5, 1000.00, 85.00);

        $this->mockDbTransaction();
        
        $this->repositoryMock
            ->shouldReceive('getDailySalesReport')
            ->once()
            ->with($date)
            ->andReturn($salesData);

        $result = $this->service->generateDailySalesReport($date);

        $this->assertEquals('Admin', $result->userName);
    }

    public function test_generate_daily_sales_report_with_transaction_rollback_on_error(): void
    {
        $date = '2025-10-26';

        $this->mockDbTransaction();
        
        $this->repositoryMock
            ->shouldReceive('getDailySalesReport')
            ->once()
            ->with($date)
            ->andThrow(new \Exception('Database connection failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection failed');

        $this->service->generateDailySalesReport($date);
    }

    public function test_generate_daily_sales_report_with_specific_date_format(): void
    {
        $date = '2025-12-31';
        
        $salesData = $this->createDailySalesData(100, 50000.00, 4250.00);

        $this->mockDbTransaction();
        
        $this->repositoryMock
            ->shouldReceive('getDailySalesReport')
            ->once()
            ->with($date)
            ->andReturn($salesData);

        $result = $this->service->generateDailySalesReport($date);

        $this->assertEquals($date, $result->reportDate);
    }

    // ==================== Integration Tests ====================

    public function test_both_methods_can_be_called_with_same_date(): void
    {
        $date = '2025-10-26';
        
        $sellerData = collect([
            $this->createSellerSalesData(1, 'John Doe', 'john@example.com', 5, 1000.00, 85.00),
        ]);
        
        $salesData = $this->createDailySalesData(5, 1000.00, 85.00);

        DB::shouldReceive('transaction')
            ->twice()
            ->andReturnUsing(fn($callback) => $callback());
        
        $this->repositoryMock
            ->shouldReceive('getDailySalesReportBySeller')
            ->once()
            ->with($date)
            ->andReturn($sellerData);
            
        $this->repositoryMock
            ->shouldReceive('getDailySalesReport')
            ->once()
            ->with($date)
            ->andReturn($salesData);

        $sellerReports = $this->service->generateDailySalesReportBySeller($date);
        $overallReport = $this->service->generateDailySalesReport($date);

        $this->assertCount(1, $sellerReports);
        $this->assertInstanceOf(DailySalesReportDTO::class, $overallReport);
        $this->assertEquals($date, $sellerReports->first()->reportDate);
        $this->assertEquals($date, $overallReport->reportDate);
    }
}
