<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\SellerController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('/v1')->group(function () {
    Route::post('/user', [UserController::class, 'store'])->name('user.store');
    Route::post('auth/login', [AuthController::class, 'login'])->name('user.login');

    Route::middleware('auth:api')->group(function () {
        Route::prefix('/auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
        });

        Route::get('/seller', [SellerController::class, 'index'])->name('seller.index');
        Route::post('/seller', [SellerController::class, 'store'])->name('seller.store');

        Route::post('/sale', [SaleController::class, 'store'])->name('sale.store');
        Route::get('/sale', [SaleController::class, 'index'])->name('sale.index');
        Route::get('/sale/seller/{sellerId}', [SaleController::class, 'showBySeller'])->name('sale.showBySeller');
    });
});
