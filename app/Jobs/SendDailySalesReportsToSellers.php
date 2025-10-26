<?php

namespace App\Jobs;

use App\Mail\DailySellerSalesReportMail;
use App\Services\Sale\Contracts\SalesReportServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailySalesReportsToSellers implements ShouldQueue
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
    public function handle(SalesReportServiceInterface $salesReportService): void
    {
        $reportDate = $this->date ?? now()->toDateString();

        Log::info('Starting to send daily sellers sales reports', [
            'date' => $reportDate,
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            $reports = $salesReportService->generateDailySalesReportBySeller($reportDate);

            if ($reports->isEmpty()) {
                Log::info('No sales found to send reports', [
                    'date' => $reportDate,
                ]);
                return;
            }

            $sentCount = 0;

            foreach ($reports as $report) {
                try {
                    Mail::to($report->sellerEmail)
                        ->send(new DailySellerSalesReportMail($report));

                    $sentCount++;

                    Log::info('Report sent successfully', [
                        'seller_id' => $report->sellerId,
                        'seller_email' => $report->sellerEmail,
                        'total_sales' => $report->totalSales,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error sending report to seller', [
                        'seller_id' => $report->sellerId,
                        'seller_email' => $report->sellerEmail,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Daily sellers sales reports sent successfully', [
                'date' => $reportDate,
                'total_reports' => $reports->count(),
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
