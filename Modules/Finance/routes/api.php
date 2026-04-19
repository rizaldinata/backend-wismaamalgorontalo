<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\DashboardController;
use Modules\Finance\Http\Controllers\ExpenseController;
use Modules\Finance\Http\Controllers\InvoiceController;
use Modules\Finance\Http\Controllers\PaymentController;
use Modules\Finance\Http\Middleware\VerifyMidtransSignature;

Route::post('/finance/payments/midtrans/notification', [PaymentController::class, 'midtransNotification'])
    ->middleware(VerifyMidtransSignature::class);

Route::prefix('finance/')->middleware(['auth:sanctum'])->group(function () {
    Route::prefix('dashboard')->middleware('permission:finance-dashboard-view')->group(function () {
        Route::get('/kpi-summary', [DashboardController::class, 'kpiSummary']);
        Route::get('/revenue-chart', [DashboardController::class, 'revenueChart']);
        Route::get('/due-invoices', [DashboardController::class, 'dueInvoices']);
        Route::get('/pending-payments', [DashboardController::class, 'pendingPayments']);
    });

    Route::prefix('expenses')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->middleware('permission:finance-expense-view');
        Route::post('/', [ExpenseController::class, 'store'])->middleware('permission:finance-expense-create');
        Route::get('/{id}', [ExpenseController::class, 'show'])->middleware('permission:finance-expense-view');
        Route::put('/{id}', [ExpenseController::class, 'update'])->middleware('permission:finance-expense-update');
        Route::delete('/{id}', [ExpenseController::class, 'destroy'])->middleware('permission:finance-expense-delete');
    });

    Route::post('/payments/{paymentId}/verify', [PaymentController::class, 'verify'])
        ->middleware('permission:finance-payment-verify');

    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->middleware('permission:finance-invoice-view');
        Route::get('/{id}', [InvoiceController::class, 'show'])->middleware('permission:finance-invoice-view');
        Route::post('/{invoiceId}/pay', [PaymentController::class, 'pay'])->middleware('permission:finance-invoice-create');
    });
});
