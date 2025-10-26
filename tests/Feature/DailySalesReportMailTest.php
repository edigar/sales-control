<?php

namespace Tests\Feature;

use App\Dto\DailySalesReportDTO;
use App\Mail\DailySalesReportMail;
use Tests\TestCase;

class DailySalesReportMailTest extends TestCase
{
    public function test_daily_sales_report_mail_can_be_rendered(): void
    {
        $report = new DailySalesReportDTO(
            userName: 'John Doe',
            totalSales: 10,
            totalAmount: 1500.50,
            totalCommission: 150.05,
            reportDate: '2025-10-26',
        );

        $mailable = new DailySalesReportMail($report);
        $rendered = $mailable->render();

        $this->assertStringContainsString('John Doe', $rendered);
        $this->assertStringContainsString('10', $rendered);
        $this->assertStringContainsString('1.500,50', $rendered);
        $this->assertStringContainsString('150,05', $rendered);
        $this->assertStringContainsString('26/10/2025', $rendered);
    }

    public function test_daily_sales_report_mail_has_correct_subject(): void
    {
        $report = new DailySalesReportDTO(
            userName: 'John Doe',
            totalSales: 10,
            totalAmount: 1500.50,
            totalCommission: 150.05,
            reportDate: '2025-10-26',
        );

        $mailable = new DailySalesReportMail($report);
        $envelope = $mailable->envelope();

        $this->assertEquals('Relatório Diário de Vendas - 26/10/2025', $envelope->subject);
    }

    public function test_daily_sales_report_mail_uses_correct_view(): void
    {
        $report = new DailySalesReportDTO(
            userName: 'John Doe',
            totalSales: 10,
            totalAmount: 1500.50,
            totalCommission: 150.05,
            reportDate: '2025-10-26',
        );

        $mailable = new DailySalesReportMail($report);
        $content = $mailable->content();

        $this->assertEquals('emails.daily-sales-report', $content->view);
    }
}
