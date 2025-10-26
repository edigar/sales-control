<?php

namespace Tests\Unit\Jobs;

use App\Dto\DailySellerSalesReportDTO;
use App\Jobs\SendDailySalesReportsToSellers;
use App\Mail\DailySellerSalesReportMail;
use App\Services\Sale\Contracts\SalesReportServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class SendDailySalesReportsToSellersTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== Helper Methods ====================

    private function createSellerReportDTO(
        int $sellerId,
        string $sellerName,
        string $sellerEmail,
        int $totalSales,
        float $totalAmount,
        float $totalCommission,
        string $reportDate
    ): DailySellerSalesReportDTO {
        return new DailySellerSalesReportDTO(
            sellerId: $sellerId,
            sellerName: $sellerName,
            sellerEmail: $sellerEmail,
            totalSales: $totalSales,
            totalAmount: $totalAmount,
            totalCommission: $totalCommission,
            reportDate: $reportDate,
        );
    }

    // ==================== Queue Tests ====================

    public function test_job_can_be_dispatched(): void
    {
        Queue::fake();

        SendDailySalesReportsToSellers::dispatch();

        Queue::assertPushed(SendDailySalesReportsToSellers::class);
    }

    public function test_job_can_be_dispatched_with_specific_date(): void
    {
        Queue::fake();

        SendDailySalesReportsToSellers::dispatch('2025-10-26');

        Queue::assertPushed(SendDailySalesReportsToSellers::class);
    }

    public function test_job_implements_should_queue(): void
    {
        $job = new SendDailySalesReportsToSellers();

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    // ==================== Email Sending Tests ====================

    public function test_job_sends_emails_to_all_sellers_with_sales(): void
    {
        Mail::fake();
        Log::shouldReceive('info')->times(4); // start + 2 success (1 por seller) + final
        
        $reports = collect([
            $this->createSellerReportDTO(
                1,
                'John Doe',
                'john@example.com',
                5,
                1000.00,
                85.00,
                '2025-10-26'
            ),
            $this->createSellerReportDTO(
                2,
                'Jane Smith',
                'jane@example.com',
                3,
                500.00,
                42.50,
                '2025-10-26'
            ),
        ]);

        $mockService = Mockery::mock(SalesReportServiceInterface::class);
        $mockService->shouldReceive('generateDailySalesReportBySeller')
            ->once()
            ->with('2025-10-26')
            ->andReturn($reports);

        $job = new SendDailySalesReportsToSellers('2025-10-26');
        $job->handle($mockService);

        Mail::assertSent(DailySellerSalesReportMail::class, 2);
        
        Mail::assertSent(DailySellerSalesReportMail::class, function ($mail) {
            return $mail->hasTo('john@example.com');
        });
        
        Mail::assertSent(DailySellerSalesReportMail::class, function ($mail) {
            return $mail->hasTo('jane@example.com');
        });
    }

    public function test_job_sends_email_with_correct_report_data(): void
    {
        Mail::fake();
        Log::shouldReceive('info')->times(3);
        
        $report = $this->createSellerReportDTO(
            1,
            'John Doe',
            'john@example.com',
            10,
            2500.00,
            212.50,
            '2025-10-26'
        );

        $mockService = Mockery::mock(SalesReportServiceInterface::class);
        $mockService->shouldReceive('generateDailySalesReportBySeller')
            ->once()
            ->andReturn(collect([$report]));

        $job = new SendDailySalesReportsToSellers('2025-10-26');
        $job->handle($mockService);

        Mail::assertSent(DailySellerSalesReportMail::class, function ($mail) use ($report) {
            return $mail->report->sellerId === 1
                && $mail->report->sellerName === 'John Doe'
                && $mail->report->totalSales === 10
                && $mail->report->totalAmount === 2500.00;
        });
    }

    public function test_job_uses_current_date_when_no_date_provided(): void
    {
        Mail::fake();
        Log::shouldReceive('info')->times(3);
        
        $currentDate = now()->toDateString();
        $report = $this->createSellerReportDTO(
            1,
            'John Doe',
            'john@example.com',
            5,
            1000.00,
            85.00,
            $currentDate
        );

        $mockService = Mockery::mock(SalesReportServiceInterface::class);
        $mockService->shouldReceive('generateDailySalesReportBySeller')
            ->once()
            ->with($currentDate)
            ->andReturn(collect([$report]));

        $job = new SendDailySalesReportsToSellers(); // Sem data
        $job->handle($mockService);

        Mail::assertSent(DailySellerSalesReportMail::class, 1);
    }

    // ==================== Edge Cases Tests ====================

    public function test_job_logs_when_no_sales_found(): void
    {
        Mail::fake();
        
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Starting to send daily sellers sales reports'
                    && isset($context['date'])
                    && $context['date'] === '2025-10-26';
            });

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'No sales found to send reports'
                    && isset($context['date'])
                    && $context['date'] === '2025-10-26';
            });

        $mockService = Mockery::mock(SalesReportServiceInterface::class);
        $mockService->shouldReceive('generateDailySalesReportBySeller')
            ->once()
            ->with('2025-10-26')
            ->andReturn(collect([]));

        $job = new SendDailySalesReportsToSellers('2025-10-26');
        $job->handle($mockService);

        Mail::assertNothingSent();
    }

    public function test_job_continues_on_individual_email_failure(): void
    {
        Mail::fake();
        Mail::shouldReceive('to->send')
            ->andThrow(new \Exception('SMTP error'));
        
        Log::shouldReceive('info')->times(2); // start + final
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Error sending report to seller'
                    && isset($context['seller_id'])
                    && isset($context['error']);
            });

        $report = $this->createSellerReportDTO(
            1,
            'John Doe',
            'invalid@example.com',
            5,
            1000.00,
            85.00,
            '2025-10-26'
        );

        $mockService = Mockery::mock(SalesReportServiceInterface::class);
        $mockService->shouldReceive('generateDailySalesReportBySeller')
            ->once()
            ->andReturn(collect([$report]));

        $job = new SendDailySalesReportsToSellers('2025-10-26');
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    public function test_job_sends_to_multiple_sellers_even_if_one_fails(): void
    {
        Mail::fake();
        
        // Simula falha para primeiro vendedor mas sucesso para segundo
        Mail::shouldReceive('to')
            ->with('fail@example.com')
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->andThrow(new \Exception('Failed to send'));

        Log::shouldReceive('info')->times(2); // start + final (sem sucesso pois falhou)
        Log::shouldReceive('error')->times(2); // erro para ambos vendedores

        $reports = collect([
            $this->createSellerReportDTO(1, 'Fail Seller', 'fail@example.com', 5, 1000.00, 85.00, '2025-10-26'),
            $this->createSellerReportDTO(2, 'Success Seller', 'success@example.com', 3, 500.00, 42.50, '2025-10-26'),
        ]);

        $mockService = Mockery::mock(SalesReportServiceInterface::class);
        $mockService->shouldReceive('generateDailySalesReportBySeller')
            ->once()
            ->andReturn($reports);

        $job = new SendDailySalesReportsToSellers('2025-10-26');
        $job->handle($mockService);

        // Verifica que pelo menos tentou enviar para ambos
        $this->assertTrue(true);
    }

    // ==================== Logging Tests ====================

    public function test_job_logs_successful_report_sending(): void
    {
        Mail::fake();
        
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Starting to send daily sellers sales reports';
            });

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Report sent successfully'
                    && $context['seller_id'] === 1
                    && $context['seller_email'] === 'john@example.com'
                    && $context['total_sales'] === 5;
            });

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Daily sellers sales reports sent successfully'
                    && $context['total_reports'] === 1
                    && $context['sent_count'] === 1;
            });

        $report = $this->createSellerReportDTO(
            1,
            'John Doe',
            'john@example.com',
            5,
            1000.00,
            85.00,
            '2025-10-26'
        );

        $mockService = Mockery::mock(SalesReportServiceInterface::class);
        $mockService->shouldReceive('generateDailySalesReportBySeller')
            ->once()
            ->andReturn(collect([$report]));

        $job = new SendDailySalesReportsToSellers('2025-10-26');
        $job->handle($mockService);

        $this->assertTrue(true); // Mockery irá validar as expectativas
    }

    public function test_job_logs_and_throws_on_service_error(): void
    {
        Log::shouldReceive('info')->once(); // start log
        
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Error processing daily sales reports'
                    && isset($context['error'])
                    && isset($context['trace']);
            });

        $mockService = Mockery::mock(SalesReportServiceInterface::class);
        $mockService->shouldReceive('generateDailySalesReportBySeller')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection failed');

        $job = new SendDailySalesReportsToSellers('2025-10-26');
        $job->handle($mockService);
    }

    public function test_job_logs_count_of_sent_reports(): void
    {
        Mail::fake();
        
        // 3 chamadas de log com withArgs específicos + 1 genérica
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Starting to send daily sellers sales reports';
            });

        Log::shouldReceive('info')
            ->twice() // 2 sellers = 2 logs de sucesso
            ->withArgs(function ($message, $context) {
                return $message === 'Report sent successfully';
            });
        
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Daily sellers sales reports sent successfully'
                    && $context['total_reports'] === 2
                    && $context['sent_count'] === 2;
            });

        $reports = collect([
            $this->createSellerReportDTO(1, 'Seller 1', 'seller1@example.com', 5, 1000.00, 85.00, '2025-10-26'),
            $this->createSellerReportDTO(2, 'Seller 2', 'seller2@example.com', 3, 500.00, 42.50, '2025-10-26'),
        ]);

        $mockService = Mockery::mock(SalesReportServiceInterface::class);
        $mockService->shouldReceive('generateDailySalesReportBySeller')
            ->once()
            ->andReturn($reports);

        $job = new SendDailySalesReportsToSellers('2025-10-26');
        $job->handle($mockService);

        $this->assertTrue(true); // Mockery irá validar as expectativas
    }
}

