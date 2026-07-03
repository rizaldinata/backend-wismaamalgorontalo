<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Models\Expense;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
});

// =========================================================
// 3.1 — Lihat Semua Pengeluaran (index)
// =========================================================

test('[BERHASIL] admin dapat mengambil daftar semua pengeluaran', function () {
    Expense::factory()->count(4)->create();

    $response = $this->getJson('/api/finance/expenses');

    $response->assertOk()
        ->assertJsonFragment(['success' => true])
        ->assertJsonFragment(['message' => 'Daftar pengeluaran berhasil diambil'])
        ->assertJsonCount(4, 'data');
});

test('[BERHASIL] daftar pengeluaran kosong jika belum ada data', function () {
    $response = $this->getJson('/api/finance/expenses');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

test('[GAGAL] parameter per_page kurang dari 1 ditolak dengan validasi error', function () {
    $response = $this->getJson('/api/finance/expenses?per_page=0');

    $response->assertUnprocessable();
});

// =========================================================
// 3.2 — Lihat Detail Pengeluaran (show)
// =========================================================

test('[BERHASIL] admin dapat melihat detail pengeluaran yang ada', function () {
    $expense = Expense::factory()->create([
        'title' => 'Beli Lampu',
        'amount' => 75000,
    ]);

    $response = $this->getJson("/api/finance/expenses/{$expense->id}");

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Detail pengeluaran berhasil diambil'])
        ->assertJsonPath('data.title', 'Beli Lampu');
});

test('[BERHASIL] response detail pengeluaran menampilkan flag is_integrated dengan benar', function () {
    $manual = Expense::factory()->create(['reference_type' => null]);
    $terintegrasi = Expense::factory()->create([
        'reference_type' => 'Modules\\Inventory\\Models\\Inventory',
        'reference_id' => 1,
    ]);

    $this->getJson("/api/finance/expenses/{$manual->id}")
        ->assertJsonPath('data.is_integrated', false)
        ->assertJsonPath('data.source', 'Manual Finance');

    $this->getJson("/api/finance/expenses/{$terintegrasi->id}")
        ->assertJsonPath('data.is_integrated', true)
        ->assertJsonPath('data.source', 'Inventory / Sistem');
});

test('[GAGAL] menampilkan 404 jika pengeluaran tidak ditemukan', function () {
    $response = $this->getJson('/api/finance/expenses/9999');

    $response->assertNotFound();
});

// =========================================================
// 3.3 — Tambah Pengeluaran Manual (store)
// =========================================================

test('[BERHASIL] admin dapat mencatat pengeluaran manual baru', function () {
    $response = $this->postJson('/api/finance/expenses', [
        'title' => 'Beli Deterjen',
        'description' => 'Untuk kebutuhan laundry',
        'amount' => 50000,
        'expense_date' => now()->toDateString(),
    ]);

    $response->assertCreated()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Pengeluaran berhasil dicatat'])
        ->assertJsonPath('data.title', 'Beli Deterjen');

    $this->assertDatabaseHas('expenses', ['title' => 'Beli Deterjen', 'amount' => 50000]);
});

test('[BERHASIL] pengeluaran manual tersimpan dengan reference_type null', function () {
    $this->postJson('/api/finance/expenses', [
        'title' => 'Beli Sabun',
        'amount' => 15000,
        'expense_date' => now()->toDateString(),
    ]);

    $this->assertDatabaseHas('expenses', [
        'title' => 'Beli Sabun',
        'reference_type' => null,
    ]);
});

test('[GAGAL] store gagal jika title tidak disertakan', function () {
    $response = $this->postJson('/api/finance/expenses', [
        'amount' => 50000,
        'expense_date' => now()->toDateString(),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

test('[GAGAL] store gagal jika amount kurang dari 1', function () {
    $response = $this->postJson('/api/finance/expenses', [
        'title' => 'Pengeluaran Nol',
        'amount' => 0,
        'expense_date' => now()->toDateString(),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

test('[GAGAL] store gagal jika expense_date bukan format tanggal yang valid', function () {
    $response = $this->postJson('/api/finance/expenses', [
        'title' => 'Pengeluaran Salah Tanggal',
        'amount' => 10000,
        'expense_date' => 'bukan-tanggal',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['expense_date']);
});

// =========================================================
// 3.4 — Edit Pengeluaran (update)
// =========================================================

test('[BERHASIL] admin dapat mengubah pengeluaran manual', function () {
    $expense = Expense::factory()->create([
        'title' => 'Pengeluaran Lama',
        'amount' => 100000,
        'reference_type' => null,
    ]);

    $response = $this->putJson("/api/finance/expenses/{$expense->id}", [
        'title' => 'Pengeluaran Diperbarui',
        'amount' => 150000,
    ]);

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Data pengeluaran berhasil diperbarui'])
        ->assertJsonPath('data.title', 'Pengeluaran Diperbarui');
});

test('[GAGAL] update gagal jika pengeluaran terintegrasi dengan inventory', function () {
    $expense = Expense::factory()->create([
        'reference_type' => 'Modules\\Inventory\\Models\\Inventory',
        'reference_id' => 3,
    ]);

    $response = $this->putJson("/api/finance/expenses/{$expense->id}", [
        'title' => 'Coba Edit Paksa',
    ]);

    $response->assertForbidden();
});

test('[GAGAL] update gagal jika amount diubah menjadi kurang dari 1', function () {
    $expense = Expense::factory()->create(['reference_type' => null]);

    $response = $this->putJson("/api/finance/expenses/{$expense->id}", [
        'amount' => -500,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

// =========================================================
// 3.5 — Hapus Pengeluaran (destroy)
// =========================================================

test('[BERHASIL] admin dapat menghapus pengeluaran manual', function () {
    $expense = Expense::factory()->create(['reference_type' => null]);

    $response = $this->deleteJson("/api/finance/expenses/{$expense->id}");

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Data pengeluaran berhasil dihapus']);

    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
});

test('[GAGAL] hapus gagal jika pengeluaran terintegrasi dengan inventory', function () {
    $expense = Expense::factory()->create([
        'reference_type' => 'Modules\\Inventory\\Models\\Inventory',
        'reference_id' => 7,
    ]);

    $response = $this->deleteJson("/api/finance/expenses/{$expense->id}");

    $response->assertForbidden();
    $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
});

test('[GAGAL] hapus mengembalikan 404 jika pengeluaran tidak ditemukan', function () {
    $response = $this->deleteJson('/api/finance/expenses/9999');

    $response->assertNotFound();
});
