<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Api\V1\AuthController;
use App\Infrastructure\Http\Controllers\Api\V1\TransactionReceiptController;
use App\Infrastructure\Http\Controllers\Api\V1\WalletController;
use App\Infrastructure\Http\Controllers\Api\V1\TransactionController;

Route::get('/health', fn () => response()->json(['status' => 'ok', 'version' => 'v1']));

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {

        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',      [AuthController::class, 'me']);
        });

        Route::prefix('wallet')->middleware('throttle:60,1')->group(function () {
            Route::get('balance',   [WalletController::class, 'balance']);
            Route::post('deposit',  [WalletController::class, 'deposit']);
            Route::post('withdraw', [WalletController::class, 'withdraw']);
            Route::get('history',   [WalletController::class, 'history']);
        });

        Route::prefix('transactions')->middleware('throttle:30,1')->group(function () {
            Route::post('transfer',                       [TransactionController::class, 'transfer']);
            Route::post('reversal/request',               [TransactionController::class, 'requestReversal']);
            Route::post('reversal/{reversalId}/approve',  [TransactionController::class, 'approveReversal']);
            Route::post('reversal/{reversalId}/reject',   [TransactionController::class, 'rejectReversal']);
            Route::get('{uuid}/receipt',                  [TransactionReceiptController::class, 'download']);
        });
    });
});
