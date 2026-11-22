<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DhanController;
use App\Http\Controllers\Api\MarketController;
use App\Http\Controllers\Api\SubscriberController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'premium'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::prefix('market')->group(function () {
        Route::get('/dashboard', [MarketController::class, 'dashboard']);
        Route::get('/stocks', [MarketController::class, 'stocks']);
        Route::get('/gainers', [MarketController::class, 'gainers']);
        Route::get('/losers', [MarketController::class, 'losers']);
        Route::get('/breadth', [MarketController::class, 'breadth']);
        Route::post('/refresh', [MarketController::class, 'refresh'])->middleware('throttle:10,1');
    });

    Route::middleware('admin')->group(function () {
        Route::prefix('dhan')->group(function () {
            Route::get('/profile', [DhanController::class, 'profile']);
            Route::get('/connection-test', [DhanController::class, 'connectionTest']);
        });

        Route::prefix('admin')->group(function () {
            Route::get('/status', [AdminController::class, 'status']);
            Route::post('/import-constituents', [AdminController::class, 'importConstituents']);
        });

        Route::get('/subscribers', [SubscriberController::class, 'index']);
        Route::post('/subscribers', [SubscriberController::class, 'store']);
        Route::post('/subscribers/{user}/extend', [SubscriberController::class, 'extend']);
        Route::post('/subscribers/{user}/deactivate', [SubscriberController::class, 'deactivate']);
    });
});