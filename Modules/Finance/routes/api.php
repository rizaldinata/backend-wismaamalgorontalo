<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\DashboardController;
use Modules\Finance\Http\Controllers\PaymentController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/invoice/{invoiceId}/pay', [PaymentController::class, 'pay']);
    Route::post('/payment/{paymentId}/verifiy', [PaymentController::class, 'verifiy']);

    Route::get('/dashboard/kpi-summary', [DashboardController::class, 'kpiSummary']);
    Route::get('/dashboard/revenue-chart', [DashboardController::class, 'revenueChart']);
    Route::get('/dashboard/due-invoices', [DashboardController::class, 'dueInvoices']);
    Route::get('/dashboard/pending-payment', [DashboardController::class, 'pendingPayments']);
});
