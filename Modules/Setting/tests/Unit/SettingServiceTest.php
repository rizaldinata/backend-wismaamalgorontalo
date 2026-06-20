<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Setting\Models\AppSetting;
use Modules\Setting\Services\SettingService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// =========================================================
// isFeatureEnabled / isMidtransEnabled / isDailyRentalEnabled
// =========================================================

test('[BERHASIL] isMidtransEnabled mengembalikan true jika setting di-enable', function () {
    AppSetting::create(['key' => 'feature_payment_midtrans', 'value' => 'true']);

    expect(app(SettingService::class)->isMidtransEnabled())->toBeTrue();
});

test('[BERHASIL] isMidtransEnabled mengembalikan false jika setting di-disable', function () {
    AppSetting::create(['key' => 'feature_payment_midtrans', 'value' => 'false']);

    expect(app(SettingService::class)->isMidtransEnabled())->toBeFalse();
});

test('[BERHASIL] isMidtransEnabled mengembalikan false jika setting belum ada di database', function () {
    expect(app(SettingService::class)->isMidtransEnabled())->toBeFalse();
});

test('[BERHASIL] isDailyRentalEnabled mengembalikan true jika sewa harian aktif', function () {
    AppSetting::create(['key' => 'feature_daily_rental', 'value' => 'true']);

    expect(app(SettingService::class)->isDailyRentalEnabled())->toBeTrue();
});

test('[BERHASIL] isWhatsAppReceiptEnabled mengembalikan true jika notifikasi kwitansi aktif', function () {
    AppSetting::create(['key' => 'feature_whatsapp_receipt', 'value' => 'true']);

    expect(app(SettingService::class)->isWhatsAppReceiptEnabled())->toBeTrue();
});

// =========================================================
// updateSetting — konversi tipe nilai
// =========================================================

test('[BERHASIL] updateSetting menyimpan nilai boolean true sebagai string "true"', function () {
    app(SettingService::class)->updateSetting('feature_payment_midtrans', true);

    $row = AppSetting::where('key', 'feature_payment_midtrans')->first();
    expect($row->value)->toBe('true');
    expect($row->parsed_value)->toBeTrue();
});

test('[BERHASIL] updateSetting menyimpan nilai boolean false sebagai string "false"', function () {
    app(SettingService::class)->updateSetting('feature_payment_midtrans', false);

    $row = AppSetting::where('key', 'feature_payment_midtrans')->first();
    expect($row->value)->toBe('false');
    expect($row->parsed_value)->toBeFalse();
});

test('[BERHASIL] updateSetting dapat memperbarui nilai yang sudah ada', function () {
    AppSetting::create(['key' => 'feature_payment_midtrans', 'value' => 'false']);

    app(SettingService::class)->updateSetting('feature_payment_midtrans', true);

    expect(AppSetting::where('key', 'feature_payment_midtrans')->value('value'))->toBe('true');
    expect(AppSetting::where('key', 'feature_payment_midtrans')->count())->toBe(1);
});

// =========================================================
// getPublicSettings — struktur output keuangan
// =========================================================

test('[BERHASIL] getPublicSettings mengembalikan semua key keuangan yang dibutuhkan', function () {
    $result = app(SettingService::class)->getPublicSettings();

    expect($result)->toHaveKeys([
        'feature_payment_midtrans',
        'feature_daily_rental',
        'feature_whatsapp_receipt',
        'feature_whatsapp_pdf_link',
        'midtrans_enabled_payments',
        'wisma_name',
        'bank_name',
        'bank_account',
        'bank_holder',
    ]);
});

test('[BERHASIL] getPublicSettings mencerminkan nilai terkini dari database', function () {
    AppSetting::create(['key' => 'feature_payment_midtrans', 'value' => 'true']);
    AppSetting::create(['key' => 'feature_daily_rental',     'value' => 'false']);

    $result = app(SettingService::class)->getPublicSettings();

    expect($result['feature_payment_midtrans'])->toBeTrue();
    expect($result['feature_daily_rental'])->toBeFalse();
});

// =========================================================
// getEnabledMidtransPaymentMethods
// =========================================================

test('[BERHASIL] getEnabledMidtransPaymentMethods mengembalikan array kosong jika belum ada setting', function () {
    expect(app(SettingService::class)->getEnabledMidtransPaymentMethods())->toBe([]);
});

test('[BERHASIL] getEnabledMidtransPaymentMethods membaca dari database dengan benar', function () {
    AppSetting::create([
        'key'   => 'midtrans_enabled_payment_methods',
        'value' => '["qris","gopay","bca_va"]',
    ]);

    $result = app(SettingService::class)->getEnabledMidtransPaymentMethods();

    expect($result)->toBe(['qris', 'gopay', 'bca_va']);
});

// =========================================================
// setEnabledMidtransPaymentMethods
// =========================================================

test('[BERHASIL] setEnabledMidtransPaymentMethods menyimpan JSON ke database', function () {
    app(SettingService::class)->setEnabledMidtransPaymentMethods(['qris', 'mandiri_va']);

    $row = AppSetting::where('key', 'midtrans_enabled_payment_methods')->first();
    expect($row)->not->toBeNull();
    expect(json_decode($row->value, true))->toBe(['qris', 'mandiri_va']);
});

test('[BERHASIL] setEnabledMidtransPaymentMethods memperbarui nilai yang sudah ada', function () {
    AppSetting::create([
        'key'   => 'midtrans_enabled_payment_methods',
        'value' => '["qris"]',
    ]);

    app(SettingService::class)->setEnabledMidtransPaymentMethods(['gopay', 'bni_va']);

    expect(AppSetting::where('key', 'midtrans_enabled_payment_methods')->count())->toBe(1);
    $saved = json_decode(
        AppSetting::where('key', 'midtrans_enabled_payment_methods')->value('value'),
        true
    );
    expect($saved)->toBe(['gopay', 'bni_va']);
});

// =========================================================
// midtransMethodCatalog
// =========================================================

test('[BERHASIL] midtransMethodCatalog mengembalikan 11 metode yang diizinkan', function () {
    $catalog = SettingService::midtransMethodCatalog();

    expect($catalog)->toHaveCount(11);
    expect(array_keys($catalog))->toContain('qris', 'gopay', 'shopeepay', 'dana', 'ovo', 'linkaja');
    expect(array_keys($catalog))->not->toContain('credit_card', 'alfamart', 'indomaret', 'akulaku');
});

// =========================================================
// getPublicSettings — midtrans_enabled_payments dari DB
// =========================================================

test('[BERHASIL] getPublicSettings mengembalikan midtrans_enabled_payments dari database', function () {
    AppSetting::create([
        'key'   => 'midtrans_enabled_payment_methods',
        'value' => '["qris","bca_va"]',
    ]);

    $result = app(SettingService::class)->getPublicSettings();

    expect($result['midtrans_enabled_payments'])->toBe(['qris', 'bca_va']);
});
