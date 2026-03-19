<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\DashboardController;
use Modules\Finance\Http\Controllers\ExpenseController;
use Modules\Finance\Http\Controllers\PaymentController;

Route::prefix('finance/')->middleware(['auth:sanctum'])->group(function () {
    Route::prefix('dashboard')->middleware('permission:finance-dashboard-view')->group(function () {
        Route::get('/kpi-summary', [DashboardController::class, 'kpiSummary']);
        Route::get('/revenue-chart', [DashboardController::class, 'revenueChart']);
        Route::get('/due-invoices', [DashboardController::class, 'dueInvoices']);
        Route::get('/pending-payments', [DashboardController::class, 'pendingPayments']);
    });

    Route::post('/payments/{paymentId}/verify', [PaymentController::class, 'verify'])
        ->middleware('permission:finance-payment-verify');

    Route::post('/invoices/{invoiceId}/pay', [PaymentController::class, 'pay'])
        ->middleware('permission:finance-invoice-create');

    Route::get('/expenses', [ExpenseController::class, 'index'])
        ->middleware('permission:finance-expense-view');
});
