<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Setting\Models\BankAccount;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createBankAccount(array $overrides = []): BankAccount
{
    return BankAccount::create(array_merge([
        'bank_name'            => 'Bank Syariah Indonesia',
        'account_number'       => '7123456789',
        'account_holder'       => 'Wisma Amal Gorontalo',
        'payment_instructions' => 'Transfer dan kirim bukti.',
        'is_active'            => true,
        'sort_order'           => 0,
    ], $overrides));
}

// ── Public endpoint ────────────────────────────────────────────────────────────

test('[BERHASIL] endpoint public bank-accounts mengembalikan hanya yang aktif', function () {
    createBankAccount(['is_active' => true]);
    createBankAccount(['bank_name' => 'BRI', 'account_number' => '0001', 'account_holder' => 'Test', 'is_active' => false]);

    $response = $this->getJson('/api/v1/settings/bank-accounts/public');

    $response->assertOk()
        ->assertJsonFragment(['status' => true]);

    expect($response->json('data'))->toHaveCount(1);
});

test('[BERHASIL] endpoint public bank-accounts dapat diakses tanpa auth', function () {
    $response = $this->getJson('/api/v1/settings/bank-accounts/public');

    $response->assertOk();
});

// ── GET /api/v1/settings/bank-accounts ────────────────────────────────────────

test('[BERHASIL] admin dapat mengambil semua rekening bank', function () {
    $this->withoutMiddleware();
    createBankAccount();
    createBankAccount(['bank_name' => 'BRI', 'account_number' => '0001', 'account_holder' => 'Test', 'is_active' => false]);

    $response = $this->getJson('/api/v1/settings/bank-accounts');

    $response->assertOk()
        ->assertJsonFragment(['status' => true]);

    expect($response->json('data'))->toHaveCount(2);
});

// ── POST /api/v1/settings/bank-accounts ───────────────────────────────────────

test('[BERHASIL] admin dapat menambahkan rekening bank baru', function () {
    $this->withoutMiddleware();

    $payload = [
        'bank_name'            => 'BSI',
        'account_number'       => '7000001234',
        'account_holder'       => 'Pemilik Wisma',
        'payment_instructions' => 'Transfer sesuai nominal.',
        'is_active'            => true,
    ];

    $response = $this->postJson('/api/v1/settings/bank-accounts', $payload);

    $response->assertStatus(201)
        ->assertJsonFragment(['bank_name' => 'BSI'])
        ->assertJsonFragment(['account_number' => '7000001234']);

    $this->assertDatabaseHas('bank_accounts', ['bank_name' => 'BSI', 'account_number' => '7000001234']);
});

test('[GAGAL] validasi gagal jika bank_name kosong', function () {
    $this->withoutMiddleware();

    $response = $this->postJson('/api/v1/settings/bank-accounts', [
        'account_number' => '123',
        'account_holder' => 'Test',
    ]);

    $response->assertStatus(422);
});

// ── PUT /api/v1/settings/bank-accounts/{id} ───────────────────────────────────

test('[BERHASIL] admin dapat memperbarui rekening bank', function () {
    $this->withoutMiddleware();
    $account = createBankAccount();

    $response = $this->putJson("/api/v1/settings/bank-accounts/{$account->id}", [
        'bank_name'      => 'BCA',
        'account_number' => '8888888',
        'account_holder' => 'Pemilik Baru',
    ]);

    $response->assertOk()
        ->assertJsonFragment(['bank_name' => 'BCA']);

    $this->assertDatabaseHas('bank_accounts', ['id' => $account->id, 'bank_name' => 'BCA']);
});

test('[GAGAL] update rekening bank yang tidak ada mengembalikan 404', function () {
    $this->withoutMiddleware();

    $response = $this->putJson('/api/v1/settings/bank-accounts/9999', [
        'bank_name' => 'BCA',
    ]);

    $response->assertNotFound();
});

// ── DELETE /api/v1/settings/bank-accounts/{id} ────────────────────────────────

test('[BERHASIL] admin dapat menghapus rekening bank', function () {
    $this->withoutMiddleware();
    $account = createBankAccount();

    $response = $this->deleteJson("/api/v1/settings/bank-accounts/{$account->id}");

    $response->assertOk()
        ->assertJsonFragment(['status' => true]);

    $this->assertDatabaseMissing('bank_accounts', ['id' => $account->id]);
});

test('[GAGAL] hapus rekening bank yang tidak ada mengembalikan 404', function () {
    $this->withoutMiddleware();

    $response = $this->deleteJson('/api/v1/settings/bank-accounts/9999');

    $response->assertNotFound();
});
