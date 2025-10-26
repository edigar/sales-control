<?php

use App\Jobs\SendDailySalesReportsToAdmin;
use App\Jobs\SendDailySalesReportsToSellers;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SendDailySalesReportsToAdmin())
    ->dailyAt('23:00')
    ->timezone('America/Sao_Paulo')
    ->name('send-daily-sales-reports-to-admin')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::job(new SendDailySalesReportsToSellers())
    ->dailyAt('23:59')
    ->timezone('America/Sao_Paulo')
    ->name('send-daily-sales-reports')
    ->withoutOverlapping()
    ->onOneServer();

