<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\DhanController;
use App\Http\Controllers\Api\MarketController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'nifty50-tracker']));

Route::prefix('dhan')->group(function () {
    Route::get('/profile', [DhanController::class, 'profile']);
    Route::get('/connection-test', [DhanController::class, 'connectionTest']);
});

Route::prefix('market')->group(function () {
    Route::get('/dashboard', [MarketController::class, 'dashboard']);
    Route::get('/stocks', [MarketController::class, 'stocks']);
    Route::get('/gainers', [MarketController::class, 'gainers']);
    Route::get('/losers', [MarketController::class, 'losers']);
    Route::get('/breadth', [MarketController::class, 'breadth']);
    Route::post('/refresh', [MarketController::class, 'refresh']);
});

Route::prefix('admin')->group(function () {
    Route::get('/status', [AdminController::class, 'status']);
    Route::post('/import-constituents', [AdminController::class, 'importConstituents']);
});