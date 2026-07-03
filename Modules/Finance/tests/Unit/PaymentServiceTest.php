<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Services\FinanceService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// =========================================================
// 2.3 — processPayment: kasus tambahan
// =========================================================

test('[GAGAL] processPayment menolak metode pembayaran yang tidak dikenal', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);
    $service = app(FinanceService::class);

    expect(fn () => $service->processPayment($invoice->id, [
        'payment_method' => 'bitcoin',
    ]))->toThrow(\InvalidArgumentException::class, 'Metode tidak didukung');
});

// =========================================================
// 2.4 — verifyPayment: kasus tambahan
// =========================================================

test('[GAGAL] verifyPayment menolak pembayaran yang sudah diverifikasi sebelumnya', function () {
    $payment = Payment::factory()->create(['status' => PaymentStatus::VERIFIED]);
    $service = app(FinanceService::class);

    expect(fn () => $service->verifyPayment($payment->id, true))
        ->toThrow(\DomainException::class, 'Pembayaran ini sudah terproses');
});

test('[GAGAL] verifyPayment menolak pembayaran yang sudah ditolak sebelumnya', function () {
    $payment = Payment::factory()->create(['status' => PaymentStatus::REJECTED]);
    $service = app(FinanceService::class);

    expect(fn () => $service->verifyPayment($payment->id, true))
        ->toThrow(\DomainException::class, 'Pembayaran ini sudah terproses');
});

// =========================================================
// 2.5 — refundPayment: validasi sebelum panggil Midtrans
// =========================================================

test('[GAGAL] refundPayment menolak jika metode bukan midtrans', function () {
    $payment = Payment::factory()->create([
        'payment_method' => 'manual',
        'status' => PaymentStatus::PAID,
    ]);
    $service = app(FinanceService::class);

    expect(fn () => $service->refundPayment($payment->id, 'Salah transfer'))
        ->toThrow(\DomainException::class, 'Hanya metode Midtrans');
});

test('[GAGAL] refundPayment menolak jika status bukan paid', function () {
    $payment = Payment::factory()->create([
        'payment_method' => 'midtrans',
        'status' => PaymentStatus::PENDING,
    ]);
    $service = app(FinanceService::class);

    expect(fn () => $service->refundPayment($payment->id, 'Batal'))
        ->toThrow(\DomainException::class, 'Hanya metode Midtrans');
});

// =========================================================
// 2.6 — handleMidtransNotification
// =========================================================

test('[BERHASIL] webhook settlement mengubah payment menjadi paid dan invoice menjadi paid', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);
    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'payment_method' => 'midtrans',
        'transaction_id' => 'ORDER-TEST-001',
        'status' => PaymentStatus::PENDING,
    ]);
    $service = app(FinanceService::class);

    $service->handleMidtransNotification([
        'order_id' => 'ORDER-TEST-001',
        'transaction_status' => 'settlement',
    ]);

    expect($payment->fresh()->status)->toBe(PaymentStatus::PAID);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
});

test('[BERHASIL] webhook capture mengubah payment menjadi paid dan invoice menjadi paid', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);
    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'payment_method' => 'midtrans',
        'transaction_id' => 'ORDER-TEST-002',
        'status' => PaymentStatus::PENDING,
    ]);
    $service = app(FinanceService::class);

    $service->handleMidtransNotification([
        'order_id' => 'ORDER-TEST-002',
        'transaction_status' => 'capture',
    ]);

    expect($payment->fresh()->status)->toBe(PaymentStatus::PAID);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
});

test('[BERHASIL] webhook expire mengubah payment menjadi failed dan invoice tetap unpaid', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);
    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'payment_method' => 'midtrans',
        'transaction_id' => 'ORDER-TEST-003',
        'status' => PaymentStatus::PENDING,
    ]);
    $service = app(FinanceService::class);

    $service->handleMidtransNotification([
        'order_id' => 'ORDER-TEST-003',
        'transaction_status' => 'expire',
    ]);

    expect($payment->fresh()->status)->toBe(PaymentStatus::FAILED);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::UNPAID);
});

test('[BERHASIL] webhook cancel mengubah payment menjadi failed', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);
    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'payment_method' => 'midtrans',
        'transaction_id' => 'ORDER-TEST-004',
        'status' => PaymentStatus::PENDING,
    ]);
    $service = app(FinanceService::class);

    $service->handleMidtransNotification([
        'order_id' => 'ORDER-TEST-004',
        'transaction_status' => 'cancel',
    ]);

    expect($payment->fresh()->status)->toBe(PaymentStatus::FAILED);
});

test('[BERHASIL] webhook dengan order_id tidak dikenal diabaikan tanpa error', function () {
    $service = app(FinanceService::class);

    expect(fn () => $service->handleMidtransNotification([
        'order_id' => 'ORDER-TIDAK-ADA',
        'transaction_status' => 'settlement',
    ]))->not->toThrow(\Exception::class);
});
