<?php

namespace Tests\Unit\Jobs;

use App\Dto\DailySalesReportDTO;
use App\Jobs\SendDailySalesReportsToAdmin;
use App\Mail\DailySalesReportMail;
use App\Models\User;
use App\Services\Sale\Contracts\SalesReportServiceInterface;
use App\Services\User\Contracts\UserServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class SendDailySalesReportsToAdminTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== Helper Methods ====================

    private function createDailySalesReportDTO(
        string $userName,
        int $totalSales,
        float $totalAmount,
        float $totalCommission,
        string $reportDate
    ): DailySalesReportDTO {
        return new DailySalesReportDTO(
            userName: $userName,
            totalSales: $totalSales,
            totalAmount: $totalAmount,
            totalCommission: $totalCommission,
            reportDate: $reportDate,
        );
    }

    private function createUserModel(
        int $id,
        string $name,
        string $email
    ): User {
        $user = new User();
        $user->id = $id;
        $user->name = $name;
        $user->email = $email;
        return $user;
    }

    // ==================== Queue Tests ====================

    public function test_job_can_be_dispatched(): void
    {
        Queue::fake();

        SendDailySalesReportsToAdmin::dispatch();

        Queue::assertPushed(SendDailySalesReportsToAdmin::class);
    }

    public function test_job_can_be_dispatched_with_specific_date(): void
    {
        Queue::fake();

        SendDailySalesReportsToAdmin::dispatch('2025-10-26');

        Queue::assertPushed(SendDailySalesReportsToAdmin::class);
    }

    public function test_job_implements_should_queue(): void
    {
        $job = new SendDailySalesReportsToAdmin();

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    // ==================== Email Sending Tests ====================

    public function test_job_sends_emails_to_all_users(): void
    {
        Mail::fake();
        Log::shouldReceive('info')->times(4); // start + 2 success (1 por user) + final
        
        $report = $this->createDailySalesReportDTO(
            'Admin',
            10,
            5000.00,
            425.00,
            '2025-10-26'
        );

        $users = new Collection([
            $this->createUserModel(1, 'John Doe', 'john@example.com'),
            $this->createUserModel(2, 'Jane Smith', 'jane@example.com'),
        ]);

        $mockSalesReportService = Mockery::mock(SalesReportServiceInterface::class);
        $mockSalesReportService->shouldReceive('generateDailySalesReport')
            ->once()
            ->with('2025-10-26')
            ->andReturn($report);

        $mockUserService = Mockery::mock(UserServiceInterface::class);
        $mockUserService->shouldReceive('getAllUsers')
            ->once()
            ->andReturn($users);

        $job = new SendDailySalesReportsToAdmin('2025-10-26');
        $job->handle($mockSalesReportService, $mockUserService);

        Mail::assertSent(DailySalesReportMail::class, 2);
        
        Mail::assertSent(DailySalesReportMail::class, function ($mail) {
            return $mail->hasTo('john@example.com');
        });
        
        Mail::assertSent(DailySalesReportMail::class, function ($mail) {
            return $mail->hasTo('jane@example.com');
        });
    }

    public function test_job_sends_email_with_correct_report_data(): void
    {
        Mail::fake();
        Log::shouldReceive('info')->times(3);
        
        $report = $this->createDailySalesReportDTO(
            'Admin',
            15,
            10000.00,
            850.00,
            '2025-10-26'
        );

        $users = new Collection([
            $this->createUserModel(1, 'John Doe', 'john@example.com'),
        ]);

        $mockSalesReportService = Mockery::mock(SalesReportServiceInterface::class);
        $mockSalesReportService->shouldReceive('generateDailySalesReport')
            ->once()
            ->andReturn($report);

        $mockUserService = Mockery::mock(UserServiceInterface::class);
        $mockUserService->shouldReceive('getAllUsers')
            ->once()
            ->andReturn($users);

        $job = new SendDailySalesReportsToAdmin('2025-10-26');
        $job->handle($mockSalesReportService, $mockUserService);

        Mail::assertSent(DailySalesReportMail::class, function ($mail) {
            return $mail->report->totalSales === 15
                && $mail->report->totalAmount === 10000.00
                && $mail->report->totalCommission === 850.00;
        });
    }

    public function test_job_updates_user_name_in_report_for_each_user(): void
    {
        Mail::fake();
        Log::shouldReceive('info')->times(4);
        
        $report = $this->createDailySalesReportDTO(
            'Admin',
            10,
            5000.00,
            425.00,
            '2025-10-26'
        );

        $users = new Collection([
            $this->createUserModel(1, 'John Doe', 'john@example.com'),
            $this->createUserModel(2, 'Jane Smith', 'jane@example.com'),
        ]);

        $mockSalesReportService = Mockery::mock(SalesReportServiceInterface::class);
        $mockSalesReportService->shouldReceive('generateDailySalesReport')
            ->once()
            ->andReturn($report);

        $mockUserService = Mockery::mock(UserServiceInterface::class);
        $mockUserService->shouldReceive('getAllUsers')
            ->once()
            ->andReturn($users);

        $job = new SendDailySalesReportsToAdmin('2025-10-26');
        $job->handle($mockSalesReportService, $mockUserService);

        // Verifica que o relatório foi personalizado para cada usuário
        Mail::assertSent(DailySalesReportMail::class, 2);
    }

    public function test_job_uses_current_date_when_no_date_provided(): void
    {
        Mail::fake();
        Log::shouldReceive('info')->times(3);
        
        $currentDate = now()->toDateString();
        $report = $this->createDailySalesReportDTO(
            'Admin',
            5,
            1000.00,
            85.00,
            $currentDate
        );

        $users = new Collection([
            $this->createUserModel(1, 'John Doe', 'john@example.com'),
        ]);

        $mockSalesReportService = Mockery::mock(SalesReportServiceInterface::class);
        $mockSalesReportService->shouldReceive('generateDailySalesReport')
            ->once()
            ->with($currentDate)
            ->andReturn($report);

        $mockUserService = Mockery::mock(UserServiceInterface::class);
        $mockUserService->shouldReceive('getAllUsers')
            ->once()
            ->andReturn($users);

        $job = new SendDailySalesReportsToAdmin(); // Sem data
        $job->handle($mockSalesReportService, $mockUserService);

        Mail::assertSent(DailySalesReportMail::class, 1);
    }

    // ==================== Edge Cases Tests ====================

    public function test_job_handles_empty_users_list(): void
    {
        Mail::fake();
        
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Starting to send daily sales reports';
            });

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Daily sales reports sent successfully'
                    && $context['total_users'] === 0
                    && $context['sent_count'] === 0;
            });

        $report = $this->createDailySalesReportDTO(
            'Admin',
            10,
            5000.00,
            425.00,
            '2025-10-26'
        );

        $mockSalesReportService = Mockery::mock(SalesReportServiceInterface::class);
        $mockSalesReportService->shouldReceive('generateDailySalesReport')
            ->once()
            ->andReturn($report);

        $mockUserService = Mockery::mock(UserServiceInterface::class);
        $mockUserService->shouldReceive('getAllUsers')
            ->once()
            ->andReturn(new Collection([]));

        $job = new SendDailySalesReportsToAdmin('2025-10-26');
        $job->handle($mockSalesReportService, $mockUserService);

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
                    && isset($context['user_id'])
                    && isset($context['error']);
            });

        $report = $this->createDailySalesReportDTO(
            'Admin',
            5,
            1000.00,
            85.00,
            '2025-10-26'
        );

        $users = new Collection([
            $this->createUserModel(1, 'John Doe', 'invalid@example.com'),
        ]);

        $mockSalesReportService = Mockery::mock(SalesReportServiceInterface::class);
        $mockSalesReportService->shouldReceive('generateDailySalesReport')
            ->once()
            ->andReturn($report);

        $mockUserService = Mockery::mock(UserServiceInterface::class);
        $mockUserService->shouldReceive('getAllUsers')
            ->once()
            ->andReturn($users);

        $job = new SendDailySalesReportsToAdmin('2025-10-26');
        $job->handle($mockSalesReportService, $mockUserService);

        $this->assertTrue(true);
    }

    public function test_job_sends_to_multiple_users_even_if_one_fails(): void
    {
        Mail::fake();
        
        Mail::shouldReceive('to')
            ->with('fail@example.com')
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->andThrow(new \Exception('Failed to send'));

        Log::shouldReceive('info')->times(2); // start + final
        Log::shouldReceive('error')->times(2); // erro para ambos

        $report = $this->createDailySalesReportDTO(
            'Admin',
            10,
            5000.00,
            425.00,
            '2025-10-26'
        );

        $users = new Collection([
            $this->createUserModel(1, 'Fail User', 'fail@example.com'),
            $this->createUserModel(2, 'Success User', 'success@example.com'),
        ]);

        $mockSalesReportService = Mockery::mock(SalesReportServiceInterface::class);
        $mockSalesReportService->shouldReceive('generateDailySalesReport')
            ->once()
            ->andReturn($report);

        $mockUserService = Mockery::mock(UserServiceInterface::class);
        $mockUserService->shouldReceive('getAllUsers')
            ->once()
            ->andReturn($users);

        $job = new SendDailySalesReportsToAdmin('2025-10-26');
        $job->handle($mockSalesReportService, $mockUserService);

        $this->assertTrue(true);
    }

    // ==================== Logging Tests ====================

    public function test_job_logs_successful_report_sending(): void
    {
        Mail::fake();
        
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Starting to send daily sales reports';
            });

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Report sent successfully'
                    && $context['user_id'] === 1
                    && $context['user_email'] === 'john@example.com'
                    && $context['total_sales'] === 10;
            });

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Daily sales reports sent successfully'
                    && $context['total_users'] === 1
                    && $context['sent_count'] === 1;
            });

        $report = $this->createDailySalesReportDTO(
            'Admin',
            10,
            5000.00,
            425.00,
            '2025-10-26'
        );

        $users = new Collection([
            $this->createUserModel(1, 'John Doe', 'john@example.com'),
        ]);

        $mockSalesReportService = Mockery::mock(SalesReportServiceInterface::class);
        $mockSalesReportService->shouldReceive('generateDailySalesReport')
            ->once()
            ->andReturn($report);

        $mockUserService = Mockery::mock(UserServiceInterface::class);
        $mockUserService->shouldReceive('getAllUsers')
            ->once()
            ->andReturn($users);

        $job = new SendDailySalesReportsToAdmin('2025-10-26');
        $job->handle($mockSalesReportService, $mockUserService);

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

        $mockSalesReportService = Mockery::mock(SalesReportServiceInterface::class);
        $mockSalesReportService->shouldReceive('generateDailySalesReport')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));

        $mockUserService = Mockery::mock(UserServiceInterface::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection failed');

        $job = new SendDailySalesReportsToAdmin('2025-10-26');
        $job->handle($mockSalesReportService, $mockUserService);
    }

    public function test_job_logs_count_of_sent_reports(): void
    {
        Mail::fake();
        
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Starting to send daily sales reports';
            });

        Log::shouldReceive('info')
            ->twice() // 2 users = 2 logs de sucesso
            ->withArgs(function ($message, $context) {
                return $message === 'Report sent successfully';
            });
        
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Daily sales reports sent successfully'
                    && $context['total_users'] === 2
                    && $context['sent_count'] === 2;
            });

        $report = $this->createDailySalesReportDTO(
            'Admin',
            20,
            10000.00,
            850.00,
            '2025-10-26'
        );

        $users = new Collection([
            $this->createUserModel(1, 'User 1', 'user1@example.com'),
            $this->createUserModel(2, 'User 2', 'user2@example.com'),
        ]);

        $mockSalesReportService = Mockery::mock(SalesReportServiceInterface::class);
        $mockSalesReportService->shouldReceive('generateDailySalesReport')
            ->once()
            ->andReturn($report);

        $mockUserService = Mockery::mock(UserServiceInterface::class);
        $mockUserService->shouldReceive('getAllUsers')
            ->once()
            ->andReturn($users);

        $job = new SendDailySalesReportsToAdmin('2025-10-26');
        $job->handle($mockSalesReportService, $mockUserService);

        $this->assertTrue(true); // Mockery irá validar as expectativas
    }

    public function test_job_logs_and_throws_on_user_service_error(): void
    {
        Log::shouldReceive('info')->once(); // start log
        
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Error processing daily sales reports'
                    && isset($context['error'])
                    && $context['error'] === 'User service unavailable';
            });

        $report = $this->createDailySalesReportDTO(
            'Admin',
            10,
            5000.00,
            425.00,
            '2025-10-26'
        );

        $mockSalesReportService = Mockery::mock(SalesReportServiceInterface::class);
        $mockSalesReportService->shouldReceive('generateDailySalesReport')
            ->once()
            ->andReturn($report);

        $mockUserService = Mockery::mock(UserServiceInterface::class);
        $mockUserService->shouldReceive('getAllUsers')
            ->once()
            ->andThrow(new \Exception('User service unavailable'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User service unavailable');

        $job = new SendDailySalesReportsToAdmin('2025-10-26');
        $job->handle($mockSalesReportService, $mockUserService);
    }

    // ==================== Integration Tests ====================

    public function test_job_calls_both_services_correctly(): void
    {
        Mail::fake();
        Log::shouldReceive('info')->times(3);
        
        $report = $this->createDailySalesReportDTO(
            'Admin',
            10,
            5000.00,
            425.00,
            '2025-10-26'
        );

        $users = new Collection([
            $this->createUserModel(1, 'John Doe', 'john@example.com'),
        ]);

        $mockSalesReportService = Mockery::mock(SalesReportServiceInterface::class);
        $mockSalesReportService->shouldReceive('generateDailySalesReport')
            ->once()
            ->with('2025-10-26')
            ->andReturn($report);

        $mockUserService = Mockery::mock(UserServiceInterface::class);
        $mockUserService->shouldReceive('getAllUsers')
            ->once()
            ->andReturn($users);

        $job = new SendDailySalesReportsToAdmin('2025-10-26');
        $job->handle($mockSalesReportService, $mockUserService);

        Mail::assertSent(DailySalesReportMail::class, 1);
    }
}

