<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Services\ExpenseService;
use Modules\Finance\Services\FinanceService;
use Modules\Rental\Models\Lease;
use Tests\TestCase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(TestCase::class, RefreshDatabase::class);

test('dapat memproses pembayaran manual dengan mengunggah bukti transfer', function () {
    Storage::fake('public');

    $lease = Lease::factory()->create();

    $invoice = Invoice::factory()->create([
        'lease_id' => $lease->id,
        'status' => InvoiceStatus::UNPAID
    ]);

    $file = UploadedFile::fake()->image('transfer_proof.jpg');
    $service = app(FinanceService::class);

    $payment = $service->processPayment($invoice->id, [
        'payment_method' => 'manual',
        'payment_proof' => $file
    ]);

    expect($payment->status)->toBe(PaymentStatus::PENDING);
    expect(Storage::disk('public')->exists($payment->payment_proof_path))->toBeTrue();
});

test('gagal memproses pembayaran jika invoice sudah lunas', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::PAID]);
    $service = app(FinanceService::class);

    expect(fn() => $service->processPayment($invoice->id, ['payment_method' => 'manual']))
        ->toThrow(\DomainException::class, 'Tagihan ini sudah lunas.');
});

test('admin dapat menyetujui pembayaran dan melunasi invoice', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);
    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'status' => PaymentStatus::PENDING
    ]);

    $service = app(FinanceService::class);
    $result = $service->verifyPayment($payment->id, true, 'Bukti transfer sesuai');

    expect($result->status)->toBe(PaymentStatus::VERIFIED);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
});

test('admin dapat menolak pembayaran dengan alasan tertentu', function () {
    $payment = Payment::factory()->create(['status' => PaymentStatus::PENDING]);

    $service = app(FinanceService::class);
    $result = $service->verifyPayment($payment->id, false, 'Nominal tidak sesuai');

    expect($result->status)->toBe(PaymentStatus::REJECTED);
    expect($result->admin_notes)->toBe('Nominal tidak sesuai');
});

test('dapat mencatat pengeluaran manual baru', function () {
    $service = app(ExpenseService::class);
    $data = [
        'title' => 'Pembelian Alat Kebersihan',
        'amount' => 150000,
        'expense_date' => now()->toDateString(),
    ];

    $expense = $service->createManualExpense($data);

    expect($expense->title)->toBe('Pembelian Alat Kebersihan');
    assertDatabaseHas('expenses', ['amount' => 150000]);
});

test('tidak bisa menghapus pengeluaran yang terintegrasi (reference_type tidak null)', function () {
    $expense = Expense::factory()->create([
        'reference_type' => 'Modules\Inventory\Models\Stock',
        'reference_id' => 99
    ]);

    $service = app(ExpenseService::class);

    expect(fn() => $service->deleteManualExpense($expense))
        ->toThrow(DomainException::class);
});

test('dapat menghapus pengeluaran manual biasa', function () {
    $expense = Expense::factory()->create(['reference_type' => null]);
    $service = app(ExpenseService::class);

    $result = $service->deleteManualExpense($expense);

    expect($result)->toBeTrue();
    assertDatabaseMissing('expenses', ['id' => $expense->id]);
});
