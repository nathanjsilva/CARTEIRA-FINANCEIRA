<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Wallet routes
    Route::prefix('wallet')->group(function () {
        Route::get('balance', [WalletController::class, 'balance']);
        Route::post('deposit', [WalletController::class, 'deposit']);
        Route::post('withdraw', [WalletController::class, 'withdraw']);
        Route::post('transfer', [WalletController::class, 'transfer']);
        Route::get('history', [WalletController::class, 'history']);
    });

    // Transaction routes
    Route::prefix('transactions')->group(function () {
        Route::post('reversal/request', [TransactionController::class, 'requestReversal']);
        Route::post('reversal/{reversalId}/approve', [TransactionController::class, 'approveReversal']);
        Route::post('reversal/{reversalId}/reject', [TransactionController::class, 'rejectReversal']);
        Route::get('reversals/pending', [TransactionController::class, 'getPendingReversals']);
        Route::get('reversals/history', [TransactionController::class, 'getReversalHistory']);
    });
});


