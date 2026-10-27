<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\InvoiceClientController;
use App\Http\Controllers\Api\InvoiceOtherController;
use App\Http\Controllers\Api\InvoiceSubcontractorController;
use App\Http\Controllers\Api\InvoiceSupplierController;
use App\Http\Controllers\Api\ProductLineController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\WorkforceController;
use App\Http\Controllers\Api\WorksiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function (): void {

    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:6,1')
            ->name('register');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:6,1')
            ->name('login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('logout-everywhere', [AuthController::class, 'logoutEverywhere'])->name('logout-everywhere');
        });
    });

    Route::middleware('auth:sanctum')->prefix('profile')->name('profile.')->group(function (): void {
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('password', [ProfileController::class, 'updatePassword'])->name('password');
    });

    Route::apiResource('worksites', WorksiteController::class)
        ->parameter('worksites', 'worksite_id')
        ->whereNumber('worksite_id');

    Route::apiResource('clients', ClientController::class)
        ->parameter('clients', 'client_id')
        ->whereNumber('client_id');

    Route::apiResource('quotes', QuoteController::class)
        ->parameter('quotes', 'quote_id')
        ->whereNumber('quote_id');

    Route::apiResource('product_lines', ProductLineController::class)
        ->parameter('product_lines', 'product_line_id')
        ->whereNumber('product_line_id');

    Route::apiResource('invoice_clients', InvoiceClientController::class)
        ->only('index');

    Route::apiResource('invoice_others', InvoiceOtherController::class)
        ->only('index');

    Route::apiResource('invoice_subcontractors', InvoiceSubcontractorController::class)
        ->only('index');

    Route::apiResource('invoice_suppliers', InvoiceSupplierController::class)
        ->only('index');

    Route::apiResource('workforces', WorkforceController::class)
        ->only('index');
});
