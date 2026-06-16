<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// Semua test di file ini melewati middleware auth & permission
// sehingga fokus pengujian adalah pada logika controller
beforeEach(function () {
    $this->withoutMiddleware();
});

// =========================================================
// 1.1 — Lihat Semua Invoice (index)
// =========================================================

test('[BERHASIL] admin dapat mengambil daftar semua invoice', function () {
    Invoice::factory()->count(3)->create();

    $response = $this->getJson('/api/finance/invoices');

    $response->assertOk()
        ->assertJsonFragment(['success' => true])
        ->assertJsonFragment(['message' => 'Daftar tagihan berhasil diambil'])
        ->assertJsonCount(3, 'data');
});

test('[BERHASIL] daftar invoice dapat difilter berdasarkan status unpaid', function () {
    Invoice::factory()->count(2)->create(['status' => InvoiceStatus::UNPAID]);
    Invoice::factory()->count(1)->create(['status' => InvoiceStatus::PAID]);

    $response = $this->getJson('/api/finance/invoices?status=unpaid');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('[BERHASIL] daftar invoice dapat difilter berdasarkan status paid', function () {
    Invoice::factory()->count(2)->create(['status' => InvoiceStatus::UNPAID]);
    Invoice::factory()->count(3)->create(['status' => InvoiceStatus::PAID]);

    $response = $this->getJson('/api/finance/invoices?status=paid');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('[BERHASIL] daftar invoice kosong jika belum ada data', function () {
    $response = $this->getJson('/api/finance/invoices');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

test('[GAGAL] parameter per_page kurang dari 1 ditolak dengan validasi error', function () {
    $response = $this->getJson('/api/finance/invoices?per_page=0');

    $response->assertUnprocessable();
});

// =========================================================
// 1.2 — Lihat Detail Invoice (show)
// =========================================================

test('[BERHASIL] admin dapat melihat detail invoice yang ada', function () {
    $invoice = Invoice::factory()->create([
        'invoice_number' => 'INV-20260601-0001',
        'amount'         => 600000,
        'status'         => InvoiceStatus::UNPAID,
    ]);

    $response = $this->getJson("/api/finance/invoices/{$invoice->id}");

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Detail tagihan berhasil diambil'])
        ->assertJsonPath('data.invoice_number', 'INV-20260601-0001');
});

test('[BERHASIL] detail invoice mengembalikan struktur data yang benar', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);
    Payment::factory()->create(['invoice_id' => $invoice->id]);

    $response = $this->getJson("/api/finance/invoices/{$invoice->id}");

    $response->assertOk()
        ->assertJsonStructure(['data' => ['id', 'invoice_number', 'amount', 'status', 'due_date', 'lease']]);
});

test('[GAGAL] menampilkan 404 jika invoice tidak ditemukan', function () {
    $response = $this->getJson('/api/finance/invoices/9999');

    $response->assertNotFound()
        ->assertJsonFragment(['success' => false])
        ->assertJsonFragment(['message' => 'Tagihan tidak ditemukan']);
});

// =========================================================
// 1.3 — Cetak Invoice / Generate Print Link (getPrintLink)
// =========================================================

test('[BERHASIL] admin dapat mengambil link cetak invoice', function () {
    $invoice = Invoice::factory()->create();

    $response = $this->getJson("/api/finance/invoices/{$invoice->id}/print-link");

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Link cetak berhasil dibuat'])
        ->assertJsonStructure(['data' => ['url']]);
});

test('[BERHASIL] link cetak mengandung signature dan parameter id', function () {
    $invoice = Invoice::factory()->create();

    $response = $this->getJson("/api/finance/invoices/{$invoice->id}/print-link");

    $url = $response->json('data.url');
    expect($url)->toBeString()
        ->and($url)->toContain('signature')
        ->and($url)->toContain((string) $invoice->id);
});

test('[GAGAL] menampilkan 404 jika invoice yang akan dicetak tidak ditemukan', function () {
    $response = $this->getJson('/api/finance/invoices/9999/print-link');

    $response->assertNotFound()
        ->assertJsonFragment(['success' => false])
        ->assertJsonFragment(['message' => 'Tagihan tidak ditemukan']);
});
