<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Setting\Models\AppSetting;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
});

// =========================================================
// GET /api/v1/settings/payment-methods
// =========================================================

test('[BERHASIL] admin dapat mengambil daftar metode pembayaran Midtrans', function () {
    $response = $this->getJson('/api/v1/settings/payment-methods');

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Daftar metode pembayaran Midtrans berhasil dimuat'])
        ->assertJsonStructure([
            'data' => [
                '*' => ['code', 'label', 'enabled'],
            ],
        ]);
});

test('[BERHASIL] response berisi 11 metode sesuai katalog', function () {
    $response = $this->getJson('/api/v1/settings/payment-methods');

    $response->assertOk();
    expect(count($response->json('data')))->toBe(11);
});

test('[BERHASIL] metode yang disimpan di DB ditampilkan sebagai enabled true', function () {
    AppSetting::create([
        'key'   => 'midtrans_enabled_payment_methods',
        'value' => '["qris","gopay","bca_va"]',
    ]);

    $response = $this->getJson('/api/v1/settings/payment-methods');

    $response->assertOk();

    $data    = collect($response->json('data'));
    $qris    = $data->firstWhere('code', 'qris');
    $gopay   = $data->firstWhere('code', 'gopay');
    $bcaVa   = $data->firstWhere('code', 'bca_va');
    $shopeePay = $data->firstWhere('code', 'shopeepay');

    expect($qris['enabled'])->toBeTrue();
    expect($gopay['enabled'])->toBeTrue();
    expect($bcaVa['enabled'])->toBeTrue();
    expect($shopeePay['enabled'])->toBeFalse();
});

test('[BERHASIL] semua metode disabled jika setting belum ada', function () {
    $response = $this->getJson('/api/v1/settings/payment-methods');

    $response->assertOk();
    $allDisabled = collect($response->json('data'))->every(fn ($m) => $m['enabled'] === false);
    expect($allDisabled)->toBeTrue();
});

// =========================================================
// PUT /api/v1/settings/payment-methods
// =========================================================

test('[BERHASIL] admin dapat mengaktifkan metode pembayaran pilihan', function () {
    $response = $this->putJson('/api/v1/settings/payment-methods', [
        'enabled_methods' => ['qris', 'gopay', 'mandiri_va'],
    ]);

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Metode pembayaran Midtrans berhasil diperbarui']);

    $this->assertDatabaseHas('app_settings', [
        'key' => 'midtrans_enabled_payment_methods',
    ]);

    $saved = json_decode(
        \Modules\Setting\Models\AppSetting::where('key', 'midtrans_enabled_payment_methods')->value('value'),
        true
    );
    expect($saved)->toContain('qris')->toContain('gopay')->toContain('mandiri_va');
});

test('[BERHASIL] admin dapat menonaktifkan semua metode dengan array kosong', function () {
    $response = $this->putJson('/api/v1/settings/payment-methods', [
        'enabled_methods' => [],
    ]);

    $response->assertOk();
    $data = $response->json('data');
    $allDisabled = collect($data)->every(fn ($m) => $m['enabled'] === false);
    expect($allDisabled)->toBeTrue();
});

test('[BERHASIL] update menimpa konfigurasi sebelumnya', function () {
    AppSetting::create([
        'key'   => 'midtrans_enabled_payment_methods',
        'value' => '["qris","gopay"]',
    ]);

    $this->putJson('/api/v1/settings/payment-methods', [
        'enabled_methods' => ['bca_va', 'bni_va'],
    ]);

    $saved = json_decode(
        AppSetting::where('key', 'midtrans_enabled_payment_methods')->value('value'),
        true
    );
    expect($saved)->not->toContain('qris')
        ->toContain('bca_va')
        ->toContain('bni_va');

    expect(AppSetting::where('key', 'midtrans_enabled_payment_methods')->count())->toBe(1);
});

test('[GAGAL] ditolak jika kode metode tidak valid', function () {
    $response = $this->putJson('/api/v1/settings/payment-methods', [
        'enabled_methods' => ['qris', 'credit_card'],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['enabled_methods.1']);
});

test('[GAGAL] ditolak jika kode metode yang dikecualikan dikirim', function () {
    $excluded = ['alfamart', 'indomaret', 'akulaku', 'kredivo'];

    foreach ($excluded as $code) {
        $response = $this->putJson('/api/v1/settings/payment-methods', [
            'enabled_methods' => [$code],
        ]);
        $response->assertUnprocessable();
    }
});

test('[GAGAL] ditolak jika field enabled_methods sama sekali tidak ada di body', function () {
    $response = $this->putJson('/api/v1/settings/payment-methods', ['other_field' => 'value']);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['enabled_methods']);
});
