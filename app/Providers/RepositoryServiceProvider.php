<?php

namespace App\Providers;

use App\Repositories\Sale\Contracts\SaleRepositoryInterface;
use App\Repositories\Sale\EloquentSaleRepository;
use App\Repositories\Seller\Contracts\SellerRepositoryInterface;
use App\Repositories\Seller\EloquentSellerRepository;
use App\Repositories\User\Contracts\UserRepositoryInterface;
use App\Repositories\User\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(SellerRepositoryInterface::class, EloquentSellerRepository::class);
        $this->app->bind(SaleRepositoryInterface::class, EloquentSaleRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
