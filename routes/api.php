<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceClientController;
use App\Http\Controllers\Api\InvoiceOtherController;
use App\Http\Controllers\Api\InvoiceSubcontractorController;
use App\Http\Controllers\Api\InvoiceSupplierController;
use App\Http\Controllers\Api\ProductLineController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\UserController;
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

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::prefix('profile')->name('profile.')->group(function (): void {
            Route::put('/', [ProfileController::class, 'update'])->name('update');
            Route::put('password', [ProfileController::class, 'updatePassword'])->name('password');
        });

        Route::get('users', [UserController::class, 'index'])->name('users.index');

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::patch('worksites/{worksite_id}/status', [WorksiteController::class, 'updateStatus'])
            ->whereNumber('worksite_id')
            ->name('worksites.status');
        Route::get('worksites/{worksite_id}/summary', [WorksiteController::class, 'summary'])
            ->whereNumber('worksite_id')
            ->name('worksites.summary');

        Route::apiResource('worksites', WorksiteController::class)
            ->parameter('worksites', 'worksite_id')
            ->whereNumber('worksite_id');

        Route::apiResource('clients', ClientController::class)
            ->parameter('clients', 'client_id')
            ->whereNumber('client_id');

        Route::patch('quotes/{quote_id}/status', [QuoteController::class, 'updateStatus'])
            ->whereNumber('quote_id')
            ->name('quotes.status');

        Route::apiResource('quotes', QuoteController::class)
            ->parameter('quotes', 'quote_id')
            ->whereNumber('quote_id');

        Route::apiResource('product_lines', ProductLineController::class)
            ->parameter('product_lines', 'product_line_id')
            ->whereNumber('product_line_id');

        Route::post('quotes/{quote_id}/invoice', [QuoteController::class, 'convertToInvoice'])
            ->whereNumber('quote_id')
            ->name('quotes.invoice');

        Route::patch('invoice_clients/{invoice_client_id}/payment', [InvoiceClientController::class, 'updatePayment'])
            ->whereNumber('invoice_client_id')
            ->name('invoice_clients.payment');

        Route::apiResource('invoice_clients', InvoiceClientController::class)
            ->only(['index', 'show', 'destroy'])
            ->parameter('invoice_clients', 'invoice_client_id')
            ->whereNumber('invoice_client_id');

        foreach (['invoice_suppliers' => InvoiceSupplierController::class, 'invoice_subcontractors' => InvoiceSubcontractorController::class, 'invoice_others' => InvoiceOtherController::class] as $resource => $controller) {
            $parameter = rtrim($resource, 's').'_id';

            Route::get("{$resource}/{{$parameter}}/attachment", [$controller, 'attachment'])
                ->whereNumber($parameter)
                ->name("{$resource}.attachment");
        }

        Route::apiResource('invoice_suppliers', InvoiceSupplierController::class)
            ->except('show')
            ->parameter('invoice_suppliers', 'invoice_supplier_id')
            ->whereNumber('invoice_supplier_id');

        Route::apiResource('invoice_subcontractors', InvoiceSubcontractorController::class)
            ->except('show')
            ->parameter('invoice_subcontractors', 'invoice_subcontractor_id')
            ->whereNumber('invoice_subcontractor_id');

        Route::apiResource('invoice_others', InvoiceOtherController::class)
            ->except('show')
            ->parameter('invoice_others', 'invoice_other_id')
            ->whereNumber('invoice_other_id');

        Route::apiResource('workforces', WorkforceController::class)
            ->except('show')
            ->parameter('workforces', 'workforce_id')
            ->whereNumber('workforce_id');
    });
});
