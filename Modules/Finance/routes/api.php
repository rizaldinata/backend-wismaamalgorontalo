<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\DashboardController;
use Modules\Finance\Http\Controllers\ExpenseController;
use Modules\Finance\Http\Controllers\FineController;
use Modules\Finance\Http\Controllers\FixedExpenseController;
use Modules\Finance\Http\Controllers\InvoiceController;
use Modules\Finance\Http\Controllers\PaymentController;
use Modules\Finance\Http\Controllers\PaymentMethodController;
use Modules\Finance\Http\Controllers\RefundRequestController;
use Modules\Finance\Http\Controllers\ResidentFinanceController;
use Modules\Finance\Http\Middleware\VerifyMidtransSignature;

Route::post('/finance/payments/midtrans/notification', [PaymentController::class, 'midtransNotification'])
    ->middleware(VerifyMidtransSignature::class);

Route::prefix('finance/')->middleware(['auth:sanctum'])->group(function () {
    Route::get('payment-methods', [PaymentMethodController::class, 'index'])
        ->middleware('permission:finance-me-invoice-view');

    Route::prefix('dashboard')->middleware('permission:finance-dashboard-view')->group(function () {
        Route::get('/kpi-summary', [DashboardController::class, 'kpiSummary']);
        Route::get('/revenue-chart', [DashboardController::class, 'revenueChart']);
        Route::get('/due-invoices', [DashboardController::class, 'dueInvoices']);
        Route::get('/pending-payments', [DashboardController::class, 'pendingPayments']);
        Route::get('/midtrans-monitoring', [DashboardController::class, 'midtransMonitoring'])
            ->withoutMiddleware('permission:finance-dashboard-view')
            ->middleware('permission:finance-payment-view');
    });

    Route::prefix('expenses')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->middleware('permission:finance-expense-view');
        Route::post('/', [ExpenseController::class, 'store'])->middleware('permission:finance-expense-create');
        Route::get('/{id}', [ExpenseController::class, 'show'])->middleware('permission:finance-expense-view');
        Route::put('/{id}', [ExpenseController::class, 'update'])->middleware('permission:finance-expense-update');
        Route::delete('/{id}', [ExpenseController::class, 'destroy'])->middleware('permission:finance-expense-delete');
    });

    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->middleware('permission:finance-payment-view');
        Route::get('/{id}', [PaymentController::class, 'show'])->middleware('permission:finance-payment-view');
        Route::post('/{paymentId}/verify', [PaymentController::class, 'verify'])->middleware('permission:finance-payment-verify');
        Route::post('/{paymentId}/refund', [PaymentController::class, 'refund'])->middleware('permission:finance-payment-refund');
    });

    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->middleware('permission:finance-invoice-view');
        Route::get('/{id}', [InvoiceController::class, 'show'])->middleware('permission:finance-invoice-view');
        Route::get('/{id}/print-link', [InvoiceController::class, 'getPrintLink'])->middleware('permission:finance-invoice-view');
        Route::post('/{invoiceId}/pay', [PaymentController::class, 'pay'])->middleware('permission:finance-invoice-create');
    });

    Route::prefix('fixed-expenses')->group(function () {
        Route::get('/', [FixedExpenseController::class, 'index'])->middleware('permission:finance-fixed-expense-view');
        Route::get('/status', [FixedExpenseController::class, 'status'])->middleware('permission:finance-fixed-expense-view');
        Route::post('/generate-bulan-ini', [FixedExpenseController::class, 'generateBulanIni'])->middleware('permission:finance-fixed-expense-update');
        Route::get('/{id}', [FixedExpenseController::class, 'show'])->middleware('permission:finance-fixed-expense-view');
        Route::put('/{id}', [FixedExpenseController::class, 'update'])->middleware('permission:finance-fixed-expense-update');
    });

    // Admin: manajemen denda
    Route::prefix('fines')->group(function () {
        Route::get('/', [FineController::class, 'index'])->middleware('permission:finance-fine-view');
        Route::get('/eligible-users', [FineController::class, 'eligibleUsers'])->middleware('permission:finance-fine-create');
        Route::post('/', [FineController::class, 'store'])->middleware('permission:finance-fine-create');
        Route::get('/{id}', [FineController::class, 'show'])->middleware('permission:finance-fine-view');
        Route::post('/{id}/waive', [FineController::class, 'waive'])->middleware('permission:finance-fine-waive');
        Route::post('/{id}/cancel', [FineController::class, 'cancel'])->middleware('permission:finance-fine-waive');
    });

    // Admin: kelola permintaan refund manual
    Route::prefix('refund-requests')->middleware('permission:finance-payment-refund')->group(function () {
        Route::get('/', [RefundRequestController::class, 'index']);
        Route::post('/{id}/proses', [RefundRequestController::class, 'proses']);
        Route::post('/{id}/tolak', [RefundRequestController::class, 'tolak']);
    });

    // Resident/Member Routes
    Route::prefix('me')->group(function () {
        Route::get('/summary', [ResidentFinanceController::class, 'summary'])->middleware('permission:finance-me-summary-view');
        Route::get('/invoices', [ResidentFinanceController::class, 'invoices'])->middleware('permission:finance-me-invoice-view');
        Route::get('/invoices/{id}', [ResidentFinanceController::class, 'showInvoice'])->middleware('permission:finance-me-invoice-view');
        Route::get('/payments', [ResidentFinanceController::class, 'payments'])->middleware('permission:finance-me-payment-view');
        Route::post('/leases/{scheduleId}/perpanjang', [ResidentFinanceController::class, 'perpanjangSewa'])->middleware('permission:finance-me-invoice-view');
        Route::post('/leases/{scheduleId}/perpanjang/initiate', [ResidentFinanceController::class, 'initiatePerpanjangManual'])->middleware('permission:finance-me-invoice-view');
        Route::get('/fines', [ResidentFinanceController::class, 'myFines'])->middleware('permission:finance-me-fine-view');
        Route::post('/fines/bayar', [ResidentFinanceController::class, 'bayarDenda'])->middleware('permission:finance-me-fine-view');
        Route::post('/schedules/{scheduleId}/ajukan-pembatalan-dp', [ResidentFinanceController::class, 'ajukanPembatalanDp'])->middleware('permission:finance-me-payment-view');
        Route::get('/refund-requests', [ResidentFinanceController::class, 'myRefundRequests'])->middleware('permission:finance-me-payment-view');
    });
});
