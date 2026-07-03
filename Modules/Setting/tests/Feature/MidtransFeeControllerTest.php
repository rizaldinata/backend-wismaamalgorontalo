<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Setting\Models\AppSetting;
use Modules\Setting\Services\SettingService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
});

// =========================================================
// GET /api/v1/settings/midtrans-fees
// =========================================================

test('[BERHASIL] dapat mengambil konfigurasi biaya Midtrans', function () {
    $response = $this->getJson('/api/v1/settings/midtrans-fees');

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonStructure([
            'data' => ['bearer', 'fees' => [['key', 'label', 'type']]],
        ]);
});

test('[BERHASIL] konfigurasi default merchant jika setting belum ada di DB', function () {
    $response = $this->getJson('/api/v1/settings/midtrans-fees');

    $response->assertOk();
    expect($response->json('data.bearer'))->toBe('merchant');
});

test('[BERHASIL] konfigurasi dimuat dari DB jika sudah ada', function () {
    $config = SettingService::defaultMidtransFeeConfig();
    $config['bearer'] = 'customer';
    AppSetting::create([
        'key' => 'midtrans_fee_config',
        'value' => json_encode($config),
    ]);

    $response = $this->getJson('/api/v1/settings/midtrans-fees');

    $response->assertOk();
    expect($response->json('data.bearer'))->toBe('customer');
});

test('[BERHASIL] response berisi 7 entri fee sesuai katalog', function () {
    $response = $this->getJson('/api/v1/settings/midtrans-fees');

    $response->assertOk();
    expect(count($response->json('data.fees')))->toBe(7);
});

// =========================================================
// PUT /api/v1/settings/midtrans-fees
// =========================================================

test('[BERHASIL] admin dapat memperbarui konfigurasi biaya Midtrans', function () {
    $payload = SettingService::defaultMidtransFeeConfig();
    $payload['bearer'] = 'customer';

    $response = $this->putJson('/api/v1/settings/midtrans-fees', $payload);

    $response->assertOk()
        ->assertJsonFragment(['status' => true]);

    expect($response->json('data.bearer'))->toBe('customer');
});

test('[BERHASIL] perubahan tarif fee tersimpan ke DB', function () {
    $payload = SettingService::defaultMidtransFeeConfig();
    $payload['fees']['qris']['rate'] = 1.0;

    $this->putJson('/api/v1/settings/midtrans-fees', $payload)->assertOk();

    $setting = AppSetting::where('key', 'midtrans_fee_config')->first();
    $saved = json_decode($setting->value, true);

    expect((float) $saved['fees']['qris']['rate'])->toBe(1.0);
});

test('[GAGAL] bearer wajib diisi', function () {
    $payload = SettingService::defaultMidtransFeeConfig();
    unset($payload['bearer']);

    $this->putJson('/api/v1/settings/midtrans-fees', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['bearer']);
});

test('[GAGAL] bearer hanya boleh merchant atau customer', function () {
    $payload = SettingService::defaultMidtransFeeConfig();
    $payload['bearer'] = 'admin';

    $this->putJson('/api/v1/settings/midtrans-fees', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['bearer']);
});

test('[GAGAL] tarif fee tidak boleh melebihi 100 persen', function () {
    $payload = SettingService::defaultMidtransFeeConfig();
    $payload['fees']['gopay']['rate'] = 101;

    $this->putJson('/api/v1/settings/midtrans-fees', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['fees.gopay.rate']);
});
