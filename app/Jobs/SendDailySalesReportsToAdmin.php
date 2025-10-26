<?php

namespace App\Jobs;

use App\Mail\DailySalesReportMail;
use App\Services\Sale\Contracts\SalesReportServiceInterface;
use App\Services\User\Contracts\UserServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailySalesReportsToAdmin implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly ?string $date = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(SalesReportServiceInterface $salesReportService, UserServiceInterface $userService): void
    {
        $reportDate = $this->date ?? now()->toDateString();

        Log::info('Starting to send daily sales reports', [
            'date' => $reportDate,
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            $report = $salesReportService->generateDailySalesReport($reportDate);
            $users = $userService->getAllUsers();
            $sentCount = 0;

            foreach ($users as $user) {
                try {
                    $report->userName = $user->name;
                    Mail::to($user->email)
                        ->send(new DailySalesReportMail($report));

                    $sentCount++;

                    Log::info('Report sent successfully', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'total_sales' => $report->totalSales,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error sending report to seller', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Daily sales reports sent successfully', [
                'date' => $reportDate,
                'total_users' => $users->count(),
                'sent_count' => $sentCount,
                'timestamp' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing daily sales reports', [
                'date' => $reportDate,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
