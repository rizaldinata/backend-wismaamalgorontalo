<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Models\Expense;
use Modules\Finance\Services\ExpenseService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// =========================================================
// 3.3 — createManualExpense
// =========================================================

test('[BERHASIL] createManualExpense selalu menyimpan reference_type sebagai null', function () {
    $service = app(ExpenseService::class);

    $expense = $service->createManualExpense([
        'title'        => 'Beli Sabun',
        'amount'       => 25000,
        'expense_date' => now()->toDateString(),
    ]);

    expect($expense->reference_type)->toBeNull();
    expect($expense->reference_id)->toBeNull();
});

test('[BERHASIL] createManualExpense mengisi expense_date dengan hari ini jika tidak disertakan', function () {
    $service = app(ExpenseService::class);

    $expense = $service->createManualExpense([
        'title'  => 'Beli Galon Air',
        'amount' => 20000,
    ]);

    expect($expense->expense_date->toDateString())->toBe(now()->toDateString());
});

// =========================================================
// 3.4 — updateManualExpense
// =========================================================

test('[BERHASIL] updateManualExpense berhasil mengubah data pengeluaran manual', function () {
    $expense = Expense::factory()->create([
        'title'          => 'Beli Kopi',
        'amount'         => 15000,
        'reference_type' => null,
    ]);
    $service = app(ExpenseService::class);

    $updated = $service->updateManualExpense($expense, [
        'title'  => 'Beli Kopi & Teh',
        'amount' => 30000,
    ]);

    expect($updated->title)->toBe('Beli Kopi & Teh');
    expect((float) $updated->amount)->toBe(30000.0);
});

test('[GAGAL] updateManualExpense menolak update pengeluaran yang terintegrasi dengan inventory', function () {
    $expense = Expense::factory()->create([
        'reference_type' => 'Modules\\Inventory\\Models\\Inventory',
        'reference_id'   => 5,
    ]);
    $service = app(ExpenseService::class);

    expect(fn () => $service->updateManualExpense($expense, ['title' => 'Coba Edit']))
        ->toThrow(\DomainException::class, 'terintegrasi dengan modul lain');
});

// =========================================================
// 3.7 — syncExpenseByReference (logika sinkronisasi inventaris)
// =========================================================

test('[BERHASIL] syncExpenseByReference memperbarui pengeluaran yang sudah ada jika amount valid', function () {
    $expense = Expense::factory()->create([
        'reference_id'   => 10,
        'reference_type' => 'Modules\\Inventory\\Models\\Inventory',
        'amount'         => 100000,
    ]);
    $service = app(ExpenseService::class);

    $service->syncExpenseByReference(10, 'Modules\\Inventory\\Models\\Inventory', [
        'title'  => 'Kursi Diperbarui',
        'amount' => 200000,
    ]);

    expect($expense->fresh()->amount)->toBe('200000.00');
});

test('[BERHASIL] syncExpenseByReference menghapus pengeluaran jika amount diperbarui menjadi nol', function () {
    $expense = Expense::factory()->create([
        'reference_id'   => 11,
        'reference_type' => 'Modules\\Inventory\\Models\\Inventory',
        'amount'         => 150000,
    ]);
    $service = app(ExpenseService::class);

    $service->syncExpenseByReference(11, 'Modules\\Inventory\\Models\\Inventory', [
        'title'  => 'Barang Gratis',
        'amount' => 0,
    ]);

    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
});

test('[BERHASIL] syncExpenseByReference membuat pengeluaran baru jika belum ada dan amount valid', function () {
    $service = app(ExpenseService::class);

    $service->syncExpenseByReference(99, 'Modules\\Inventory\\Models\\Inventory', [
        'title'  => 'Lemari Baru',
        'amount' => 500000,
    ]);

    $this->assertDatabaseHas('expenses', [
        'reference_id'   => 99,
        'reference_type' => 'Modules\\Inventory\\Models\\Inventory',
        'amount'         => 500000,
    ]);
});

test('[BERHASIL] syncExpenseByReference mengabaikan jika belum ada dan amount nol', function () {
    $service = app(ExpenseService::class);

    $result = $service->syncExpenseByReference(88, 'Modules\\Inventory\\Models\\Inventory', [
        'title'  => 'Barang Gratis',
        'amount' => 0,
    ]);

    expect($result)->toBeNull();
    $this->assertDatabaseMissing('expenses', ['reference_id' => 88]);
});

// =========================================================
// 3.8 — removeExpenseByReference
// =========================================================

test('[BERHASIL] removeExpenseByReference menghapus pengeluaran berdasarkan referensi', function () {
    $expense = Expense::factory()->create([
        'reference_id'   => 20,
        'reference_type' => 'Modules\\Inventory\\Models\\Inventory',
    ]);
    $service = app(ExpenseService::class);

    $result = $service->removeExpenseByReference(20, 'Modules\\Inventory\\Models\\Inventory');

    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
});

test('[BERHASIL] removeExpenseByReference mengembalikan false jika referensi tidak ditemukan', function () {
    $service = app(ExpenseService::class);

    $result = $service->removeExpenseByReference(9999, 'Modules\\Inventory\\Models\\Inventory');

    expect($result)->toBeFalse();
});
