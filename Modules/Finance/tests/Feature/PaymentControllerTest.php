<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
});

// =========================================================
// 2.1 — Lihat Semua Pembayaran (index)
// =========================================================

test('[BERHASIL] admin dapat mengambil daftar semua pembayaran', function () {
    Payment::factory()->count(3)->create();

    $response = $this->getJson('/api/finance/payments');

    $response->assertOk()
        ->assertJsonFragment(['success' => true])
        ->assertJsonFragment(['message' => 'Daftar log pembayaran berhasil diambil'])
        ->assertJsonCount(3, 'data');
});

test('[BERHASIL] daftar pembayaran dapat difilter berdasarkan status pending', function () {
    Payment::factory()->count(2)->create(['status' => PaymentStatus::PENDING]);
    Payment::factory()->count(1)->create(['status' => PaymentStatus::VERIFIED]);

    $response = $this->getJson('/api/finance/payments?status=pending');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('[BERHASIL] daftar pembayaran dapat difilter berdasarkan metode manual', function () {
    Payment::factory()->count(2)->create(['payment_method' => 'manual']);
    Payment::factory()->count(1)->create(['payment_method' => 'midtrans']);

    $response = $this->getJson('/api/finance/payments?payment_method=manual');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('[BERHASIL] daftar pembayaran kosong jika belum ada data', function () {
    $response = $this->getJson('/api/finance/payments');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

test('[GAGAL] parameter per_page kurang dari 1 ditolak dengan validasi error', function () {
    $response = $this->getJson('/api/finance/payments?per_page=0');

    $response->assertUnprocessable();
});

// =========================================================
// 2.2 — Lihat Detail Pembayaran (show)
// =========================================================

test('[BERHASIL] admin dapat melihat detail pembayaran yang ada', function () {
    $payment = Payment::factory()->create([
        'payment_method' => 'manual',
        'status'         => PaymentStatus::PENDING,
    ]);

    $response = $this->getJson("/api/finance/payments/{$payment->id}");

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Detail pembayaran berhasil diambil'])
        ->assertJsonStructure(['data' => ['id', 'invoice_id', 'payment_method', 'status']]);
});

test('[GAGAL] menampilkan 404 jika pembayaran tidak ditemukan', function () {
    $response = $this->getJson('/api/finance/payments/9999');

    $response->assertNotFound();
});

// =========================================================
// 2.3 — Bayar Invoice (pay)
// =========================================================

test('[BERHASIL] penghuni dapat membayar invoice dengan bukti transfer manual', function () {
    Storage::fake('public');
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);
    $file    = UploadedFile::fake()->image('bukti_transfer.jpg');

    $response = $this->post("/api/finance/invoices/{$invoice->id}/pay", [
        'payment_method' => 'manual',
        'payment_proof'  => $file,
    ]);

    $response->assertCreated()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Proses pembayaran berhasil diinisialisasi'])
        ->assertJsonPath('data.status', 'pending');
});

test('[BERHASIL] pembayaran manual tersimpan dengan bukti transfer di storage', function () {
    Storage::fake('public');
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);
    $file    = UploadedFile::fake()->image('bukti.jpg');

    $this->post("/api/finance/invoices/{$invoice->id}/pay", [
        'payment_method' => 'manual',
        'payment_proof'  => $file,
    ]);

    $payment = Payment::where('invoice_id', $invoice->id)->first();
    expect($payment)->not->toBeNull();
    Storage::disk('public')->assertExists($payment->payment_proof_path);
});

test('[GAGAL] bayar invoice gagal jika payment_method tidak disertakan', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);

    $response = $this->postJson("/api/finance/invoices/{$invoice->id}/pay", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['payment_method']);
});

test('[GAGAL] bayar invoice manual gagal jika bukti transfer tidak disertakan', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);

    $response = $this->postJson("/api/finance/invoices/{$invoice->id}/pay", [
        'payment_method' => 'manual',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['payment_proof']);
});

// =========================================================
// 2.4 — Verifikasi Pembayaran (verify)
// =========================================================

test('[BERHASIL] admin dapat menyetujui pembayaran dan mengubah invoice menjadi paid', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);
    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'status'     => PaymentStatus::PENDING,
    ]);

    $response = $this->postJson("/api/finance/payments/{$payment->id}/verify", [
        'is_approved' => true,
        'admin_notes' => 'Bukti transfer valid',
    ]);

    $response->assertOk()
        ->assertJsonFragment(['message' => 'Pembayaran berhasil diverifikasi.']);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
    expect($payment->fresh()->status)->toBe(PaymentStatus::VERIFIED);
});

test('[BERHASIL] admin dapat menolak pembayaran dengan catatan alasan', function () {
    $payment = Payment::factory()->create(['status' => PaymentStatus::PENDING]);

    $response = $this->postJson("/api/finance/payments/{$payment->id}/verify", [
        'is_approved' => false,
        'admin_notes' => 'Nominal tidak sesuai',
    ]);

    $response->assertOk()
        ->assertJsonFragment(['message' => 'Pembayaran ditolak.']);

    expect($payment->fresh()->status)->toBe(PaymentStatus::REJECTED);
    expect($payment->fresh()->admin_notes)->toBe('Nominal tidak sesuai');
});

test('[GAGAL] verifikasi gagal jika is_approved tidak disertakan', function () {
    $payment = Payment::factory()->create(['status' => PaymentStatus::PENDING]);

    $response = $this->postJson("/api/finance/payments/{$payment->id}/verify", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['is_approved']);
});

// =========================================================
// 2.5 — Refund Pembayaran (refund)
// =========================================================

test('[GAGAL] refund gagal jika reason tidak disertakan', function () {
    $payment = Payment::factory()->create(['status' => PaymentStatus::PENDING]);

    $response = $this->postJson("/api/finance/payments/{$payment->id}/refund", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);
});

test('[GAGAL] refund gagal jika metode pembayaran bukan midtrans', function () {
    $payment = Payment::factory()->create([
        'payment_method' => 'manual',
        'status'         => PaymentStatus::PAID,
    ]);

    $response = $this->postJson("/api/finance/payments/{$payment->id}/refund", [
        'reason' => 'Salah kamar',
    ]);

    $response->assertForbidden();
});

// =========================================================
// 2.6 — Webhook Midtrans (midtransNotification)
// =========================================================

test('[BERHASIL] webhook settlement memperbarui status payment dan invoice menjadi paid', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);
    $payment = Payment::factory()->create([
        'invoice_id'     => $invoice->id,
        'payment_method' => 'midtrans',
        'transaction_id' => 'TXN-WEBHOOK-001',
        'status'         => PaymentStatus::PENDING,
    ]);

    $response = $this->postJson('/api/finance/payments/midtrans/notification', [
        'order_id'           => 'TXN-WEBHOOK-001',
        'transaction_status' => 'settlement',
    ]);

    $response->assertOk()
        ->assertJsonFragment(['message' => 'Notifikasi diterima']);

    expect($payment->fresh()->status)->toBe(PaymentStatus::PAID);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
});

test('[BERHASIL] webhook expire memperbarui status payment menjadi failed', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);
    $payment = Payment::factory()->create([
        'invoice_id'     => $invoice->id,
        'payment_method' => 'midtrans',
        'transaction_id' => 'TXN-WEBHOOK-002',
        'status'         => PaymentStatus::PENDING,
    ]);

    $response = $this->postJson('/api/finance/payments/midtrans/notification', [
        'order_id'           => 'TXN-WEBHOOK-002',
        'transaction_status' => 'expire',
    ]);

    $response->assertOk();
    expect($payment->fresh()->status)->toBe(PaymentStatus::FAILED);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::UNPAID);
});

test('[BERHASIL] webhook dengan order_id tidak dikenal direspons 200 tanpa error', function () {
    $response = $this->postJson('/api/finance/payments/midtrans/notification', [
        'order_id'           => 'ORDER-TIDAK-ADA-999',
        'transaction_status' => 'settlement',
    ]);

    $response->assertOk();
});
