<?php

namespace App\Mail;

use App\Dto\DailySellerSalesReportDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailySellerSalesReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly DailySellerSalesReportDTO $report
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Relatório Diário de Vendas - ' . date('d/m/Y', strtotime($this->report->reportDate)),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-seller-sales-report',
            with: [
                'sellerName' => $this->report->sellerName,
                'totalSales' => $this->report->totalSales,
                'totalAmount' => $this->report->totalAmount,
                'totalCommission' => $this->report->totalCommission,
                'reportDate' => date('d/m/Y', strtotime($this->report->reportDate)),
            ],
        );
    }
}

