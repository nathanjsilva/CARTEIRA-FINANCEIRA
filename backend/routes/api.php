<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Api\V1\AuthController;
use App\Infrastructure\Http\Controllers\Api\V1\WalletController;
use App\Infrastructure\Http\Controllers\Api\V1\TransactionController;

// Health check
Route::get('/health', fn () => response()->json(['status' => 'ok', 'version' => 'v1']));

// API V1
Route::prefix('v1')->group(function () {

    // Public routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });

        // Wallet
        Route::prefix('wallet')->group(function () {
            Route::get('balance', [WalletController::class, 'balance']);
            Route::post('deposit', [WalletController::class, 'deposit']);
            Route::post('withdraw', [WalletController::class, 'withdraw']);
            Route::get('history', [WalletController::class, 'history']);
        });

        // Transactions
        Route::prefix('transactions')->group(function () {
            Route::post('transfer', [TransactionController::class, 'transfer']);
            Route::post('reversal/request', [TransactionController::class, 'requestReversal']);
            Route::post('reversal/{reversalId}/approve', [TransactionController::class, 'approveReversal']);
            Route::post('reversal/{reversalId}/reject', [TransactionController::class, 'rejectReversal']);
        });
    });
});

// Legacy routes (backward compat - same prefix as before)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    Route::prefix('wallet')->group(function () {
        Route::get('balance', [WalletController::class, 'balance']);
        Route::post('deposit', [WalletController::class, 'deposit']);
        Route::post('withdraw', [WalletController::class, 'withdraw']);
        Route::post('transfer', [TransactionController::class, 'transfer']);
        Route::get('history', [WalletController::class, 'history']);
    });

    Route::prefix('transactions')->group(function () {
        Route::post('reversal/request', [TransactionController::class, 'requestReversal']);
        Route::post('reversal/{reversalId}/approve', [TransactionController::class, 'approveReversal']);
        Route::post('reversal/{reversalId}/reject', [TransactionController::class, 'rejectReversal']);
    });
});
