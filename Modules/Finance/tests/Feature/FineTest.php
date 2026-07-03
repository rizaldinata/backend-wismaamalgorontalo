<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Finance\Enums\FineStatus;
use Modules\Finance\Models\Fine;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
});

// ─── Admin: Buat Denda ────────────────────────────────────────────────────────

test('[BERHASIL] admin dapat membuat denda untuk pengguna', function () {
    $admin = User::factory()->create();
    $tenant = User::factory()->create();

    $response = $this->actingAs($admin, 'sanctum')

        ->postJson('/api/finance/fines', [
            'tenant_user_id' => $tenant->id,
            'amount' => 50000,
            'reason' => 'Melanggar peraturan kos',
        ]);

    $response->assertStatus(201)
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['amount' => 50000.0])
        ->assertJsonFragment(['status' => 'unpaid']);

    $this->assertDatabaseHas('fines', [
        'tenant_user_id' => $tenant->id,
        'amount' => 50000,
        'reason' => 'Melanggar peraturan kos',
        'status' => 'unpaid',
    ]);
});

test('[GAGAL] admin tidak bisa membuat denda dengan amount di bawah minimum', function () {
    $admin = User::factory()->create();
    $tenant = User::factory()->create();

    $response = $this->actingAs($admin, 'sanctum')

        ->postJson('/api/finance/fines', [
            'tenant_user_id' => $tenant->id,
            'amount' => 500, // di bawah minimum 1000
            'reason' => 'Test',
        ]);

    $response->assertStatus(422);
});

// ─── Admin: Maafkan & Batalkan Denda ─────────────────────────────────────────

test('[BERHASIL] admin dapat memaafkan denda yang belum dibayar', function () {
    $admin = User::factory()->create();
    $fine = Fine::create([
        'tenant_user_id' => User::factory()->create()->id,
        'amount' => 50000,
        'reason' => 'Test',
        'status' => FineStatus::UNPAID->value,
    ]);

    $response = $this->actingAs($admin, 'sanctum')

        ->postJson("/api/finance/fines/{$fine->id}/waive", [
            'waive_reason' => 'Penghuni sudah meminta maaf dan berjanji tidak mengulangi',
        ]);

    $response->assertOk()->assertJsonFragment(['status' => 'waived']);

    $this->assertDatabaseHas('fines', [
        'id' => $fine->id,
        'status' => 'waived',
        'waive_reason' => 'Penghuni sudah meminta maaf dan berjanji tidak mengulangi',
    ]);
});

test('[GAGAL] admin tidak bisa memaafkan denda yang sudah lunas', function () {
    $admin = User::factory()->create();
    $fine = Fine::create([
        'tenant_user_id' => User::factory()->create()->id,
        'amount' => 50000,
        'reason' => 'Test',
        'status' => FineStatus::PAID->value,
    ]);

    $response = $this->actingAs($admin, 'sanctum')

        ->postJson("/api/finance/fines/{$fine->id}/waive", [
            'waive_reason' => 'Coba maafkan yang sudah lunas',
        ]);

    $response->assertStatus(403);
});

test('[BERHASIL] admin dapat membatalkan denda yang belum dibayar', function () {
    $admin = User::factory()->create();
    $fine = Fine::create([
        'tenant_user_id' => User::factory()->create()->id,
        'amount' => 50000,
        'reason' => 'Test',
        'status' => FineStatus::UNPAID->value,
    ]);

    $response = $this->actingAs($admin, 'sanctum')

        ->postJson("/api/finance/fines/{$fine->id}/cancel");

    $response->assertOk()->assertJsonFragment(['status' => 'cancelled']);
});

// ─── Resident: Lihat & Bayar Denda ───────────────────────────────────────────

test('[BERHASIL] pengguna dapat melihat daftar denda miliknya', function () {
    $user = User::factory()->create();

    Fine::create([
        'tenant_user_id' => $user->id,
        'amount' => 50000,
        'reason' => 'Denda 1',
        'status' => FineStatus::UNPAID->value,
    ]);

    Fine::create([
        'tenant_user_id' => $user->id,
        'amount' => 75000,
        'reason' => 'Denda 2',
        'status' => FineStatus::WAIVED->value,
    ]);

    $response = $this->actingAs($user, 'sanctum')

        ->getJson('/api/finance/me/fines');

    $response->assertOk()
        ->assertJsonFragment(['success' => true])
        ->assertJsonCount(2, 'data');
});

test('[BERHASIL] pengguna hanya melihat denda miliknya sendiri', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Fine::create(['tenant_user_id' => $user1->id, 'amount' => 50000, 'reason' => 'Denda user1', 'status' => FineStatus::UNPAID->value]);
    Fine::create(['tenant_user_id' => $user2->id, 'amount' => 75000, 'reason' => 'Denda user2', 'status' => FineStatus::UNPAID->value]);

    $response = $this->actingAs($user1, 'sanctum')

        ->getJson('/api/finance/me/fines');

    $response->assertOk()->assertJsonCount(1, 'data');
    $response->assertJsonFragment(['reason' => 'Denda user1']);
    $response->assertJsonMissing(['reason' => 'Denda user2']);
});

// ─── Listener: TandaiDendaLunas ───────────────────────────────────────────────

test('[BERHASIL] TandaiDendaLunas menandai denda sebagai lunas ketika invoice fine di-settle', function () {
    $user = User::factory()->create();

    $fine1 = Fine::create([
        'tenant_user_id' => $user->id,
        'amount' => 50000,
        'reason' => 'Denda 1',
        'status' => FineStatus::UNPAID->value,
    ]);

    $fine2 = Fine::create([
        'tenant_user_id' => $user->id,
        'amount' => 75000,
        'reason' => 'Denda 2',
        'status' => FineStatus::UNPAID->value,
    ]);

    $invoice = \Modules\Finance\Models\Invoice::create([
        'type' => 'fine',
        'invoice_number' => 'FINE-TEST-001',
        'amount' => 125000,
        'status' => 'unpaid',
        'due_date' => now()->toDateString(),
        'tenant_user_id' => $user->id,
        'tenant_name' => $user->name,
    ]);

    \Illuminate\Support\Facades\DB::table('fine_invoice')->insert([
        ['fine_id' => $fine1->id, 'invoice_id' => $invoice->id, 'created_at' => now(), 'updated_at' => now()],
        ['fine_id' => $fine2->id, 'invoice_id' => $invoice->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $payment = \Modules\Finance\Models\Payment::create([
        'invoice_id' => $invoice->id,
        'payment_method' => 'manual',
        'status' => 'pending',
    ]);

    event(new \Modules\Finance\Events\PaymentSettled($payment));

    $this->assertDatabaseHas('fines', ['id' => $fine1->id, 'status' => 'paid']);
    $this->assertDatabaseHas('fines', ['id' => $fine2->id, 'status' => 'paid']);
});

test('[BERHASIL] TandaiDendaLunas tidak mengubah apa pun untuk invoice bukan fine', function () {
    $user = User::factory()->create();

    $fine = Fine::create([
        'tenant_user_id' => $user->id,
        'amount' => 50000,
        'reason' => 'Test',
        'status' => FineStatus::UNPAID->value,
    ]);

    // Invoice tipe 'sewa', bukan 'fine'
    $invoice = \Modules\Finance\Models\Invoice::create([
        'type' => 'sewa',
        'invoice_number' => 'INV-TEST-001',
        'amount' => 750000,
        'status' => 'unpaid',
        'due_date' => now()->toDateString(),
        'tenant_user_id' => $user->id,
        'tenant_name' => $user->name,
    ]);

    $payment = \Modules\Finance\Models\Payment::create([
        'invoice_id' => $invoice->id,
        'payment_method' => 'manual',
        'status' => 'pending',
    ]);

    event(new \Modules\Finance\Events\PaymentSettled($payment));

    // Denda tetap unpaid karena invoice bukan tipe fine
    $this->assertDatabaseHas('fines', ['id' => $fine->id, 'status' => 'unpaid']);
});
