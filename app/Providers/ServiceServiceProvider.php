<?php

namespace App\Providers;

use App\Repositories\Sale\Contracts\SaleRepositoryInterface;
use App\Services\Sale\CommissionCalculator;
use App\Services\Sale\Contracts\CommissionCalculatorInterface;
use App\Services\Sale\Contracts\SaleCacheServiceInterface;
use App\Services\Sale\Contracts\SaleServiceInterface;
use App\Services\Sale\Contracts\SalesReportServiceInterface;
use App\Services\Sale\SaleCacheService;
use App\Services\Sale\SaleService;
use App\Services\Sale\SalesReportService;
use App\Services\Seller\Contracts\SellerServiceInterface;
use App\Services\Seller\SellerService;
use App\Services\User\Contracts\UserServiceInterface;
use App\Services\User\UserService;
use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(SellerServiceInterface::class, SellerService::class);
        $this->app->bind(SaleServiceInterface::class, function ($app) {
            $saleService = $app->make('App\Services\Sale\SaleService');
            return new SaleCacheService($saleService);
        });
        
        $this->app->bind(SalesReportServiceInterface::class, SalesReportService::class);
        $this->app->bind(CommissionCalculatorInterface::class, function () {
            return new CommissionCalculator(config('sale.commission.rate'));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
