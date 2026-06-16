<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Setting\Models\AppSetting;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
});

// =========================================================
// GET /api/v1/settings/public — endpoint publik (tanpa auth)
// =========================================================

test('[BERHASIL] endpoint public settings merespons 200 dengan struktur keuangan lengkap', function () {
    $response = $this->getJson('/api/v1/settings/public');

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Konfigurasi aplikasi berhasil dimuat'])
        ->assertJsonStructure([
            'data' => [
                'feature_payment_midtrans',
                'feature_daily_rental',
                'feature_whatsapp_receipt',
                'feature_whatsapp_pdf_link',
                'midtrans_enabled_payments',
                'wisma_name',
            ],
        ]);
});

test('[BERHASIL] public settings mengembalikan false untuk semua fitur jika belum ada konfigurasi', function () {
    $response = $this->getJson('/api/v1/settings/public');

    $response->assertOk();
    expect($response->json('data.feature_payment_midtrans'))->toBeFalse();
    expect($response->json('data.feature_daily_rental'))->toBeFalse();
    expect($response->json('data.feature_whatsapp_receipt'))->toBeFalse();
});

test('[BERHASIL] public settings mencerminkan nilai terkini dari database', function () {
    AppSetting::create(['key' => 'feature_payment_midtrans', 'value' => 'true']);
    AppSetting::create(['key' => 'feature_daily_rental',     'value' => 'true']);

    $response = $this->getJson('/api/v1/settings/public');

    $response->assertOk();
    expect($response->json('data.feature_payment_midtrans'))->toBeTrue();
    expect($response->json('data.feature_daily_rental'))->toBeTrue();
});

// =========================================================
// GET /api/v1/settings — endpoint admin (auth diperlukan)
// =========================================================

test('[BERHASIL] admin dapat membaca semua konfigurasi keuangan', function () {
    AppSetting::create(['key' => 'feature_payment_midtrans', 'value' => 'false']);

    $response = $this->getJson('/api/v1/settings');

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonStructure([
            'data' => [
                'feature_payment_midtrans',
                'feature_daily_rental',
            ],
        ]);
});

// =========================================================
// POST /api/v1/settings/update-bulk — update konfigurasi keuangan
// =========================================================

test('[BERHASIL] admin dapat mengaktifkan fitur Midtrans pembayaran', function () {
    $response = $this->postJson('/api/v1/settings/update-bulk', [
        'settings' => [
            'feature_payment_midtrans' => true,
        ],
    ]);

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonFragment(['message' => 'Seluruh konfigurasi internal berhasil diperbarui!'])
        ->assertJsonPath('data.feature_payment_midtrans', true);

    $this->assertDatabaseHas('app_settings', [
        'key'   => 'feature_payment_midtrans',
        'value' => 'true',
    ]);
});

test('[BERHASIL] admin dapat menonaktifkan fitur sewa harian', function () {
    AppSetting::create(['key' => 'feature_daily_rental', 'value' => 'true']);

    $response = $this->postJson('/api/v1/settings/update-bulk', [
        'settings' => [
            'feature_daily_rental' => false,
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.feature_daily_rental', false);

    $this->assertDatabaseHas('app_settings', [
        'key'   => 'feature_daily_rental',
        'value' => 'false',
    ]);
});

test('[BERHASIL] admin dapat update beberapa setting keuangan sekaligus', function () {
    $response = $this->postJson('/api/v1/settings/update-bulk', [
        'settings' => [
            'feature_payment_midtrans'   => true,
            'feature_whatsapp_receipt'   => true,
            'feature_whatsapp_pdf_link'  => false,
            'feature_daily_rental'       => false,
        ],
    ]);

    $response->assertOk();
    expect($response->json('data.feature_payment_midtrans'))->toBeTrue();
    expect($response->json('data.feature_whatsapp_receipt'))->toBeTrue();
    expect($response->json('data.feature_daily_rental'))->toBeFalse();
});

test('[BERHASIL] update-bulk hanya membuat satu record per key meskipun diupdate berkali-kali', function () {
    $this->postJson('/api/v1/settings/update-bulk', [
        'settings' => ['feature_payment_midtrans' => true],
    ]);
    $this->postJson('/api/v1/settings/update-bulk', [
        'settings' => ['feature_payment_midtrans' => false],
    ]);

    expect(AppSetting::where('key', 'feature_payment_midtrans')->count())->toBe(1);
    $this->assertDatabaseHas('app_settings', [
        'key'   => 'feature_payment_midtrans',
        'value' => 'false',
    ]);
});

test('[GAGAL] update-bulk ditolak jika field settings tidak disertakan', function () {
    $response = $this->postJson('/api/v1/settings/update-bulk', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['settings']);
});

test('[GAGAL] update-bulk ditolak jika nilai wisma_name melebihi 100 karakter', function () {
    $response = $this->postJson('/api/v1/settings/update-bulk', [
        'settings' => [
            'wisma_name' => str_repeat('A', 101),
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['settings.wisma_name']);
});
