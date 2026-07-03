<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Services\MidtransFeeCalculator;
use Modules\Setting\Models\AppSetting;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeFeeConfig(string $bearer = 'merchant', array $overrides = []): array
{
    $fees = array_merge([
        'bank_transfer' => ['type' => 'flat',    'amount' => 4000],
        'gopay' => ['type' => 'percent', 'rate' => 2.0],
        'qris' => ['type' => 'percent', 'rate' => 0.7],
        'shopeepay' => ['type' => 'percent', 'rate' => 2.0],
        'dana' => ['type' => 'percent', 'rate' => 1.5],
        'ovo' => ['type' => 'percent', 'rate' => 1.5],
        'linkaja' => ['type' => 'percent', 'rate' => 1.5],
    ], $overrides);

    return ['bearer' => $bearer, 'fees' => $fees];
}

function makeCalculator(string $bearer = 'merchant', array $overrides = []): MidtransFeeCalculator
{
    AppSetting::updateOrCreate(
        ['key' => 'midtrans_fee_config'],
        ['value' => json_encode(makeFeeConfig($bearer, $overrides))]
    );

    return app(MidtransFeeCalculator::class);
}

// ── isCustomerBearer ────────────────────────────────────────

test('[BERHASIL] isCustomerBearer true jika bearer = customer', function () {
    $calc = makeCalculator('customer');
    expect($calc->isCustomerBearer())->toBeTrue();
});

test('[BERHASIL] isCustomerBearer false jika bearer = merchant', function () {
    $calc = makeCalculator('merchant');
    expect($calc->isCustomerBearer())->toBeFalse();
});

// ── calculateFee: bank transfer (flat) ─────────────────────

test('[BERHASIL] fee bca_va adalah flat Rp4.000', function () {
    $calc = makeCalculator();
    expect($calc->calculateFee('bca_va', 500_000))->toBe(4000);
});

test('[BERHASIL] semua VA dikenakan fee flat yang sama', function () {
    $calc = makeCalculator();
    foreach (['bca_va', 'bni_va', 'bri_va', 'permata_va', 'mandiri_va'] as $method) {
        expect($calc->calculateFee($method, 500_000))->toBe(4000);
    }
});

// ── calculateFee: e-wallet (percent) ───────────────────────

test('[BERHASIL] fee gopay 2% dari nominal', function () {
    $calc = makeCalculator();
    expect($calc->calculateFee('gopay', 100_000))->toBe(2000);
});

test('[BERHASIL] fee qris 0.7% dari nominal', function () {
    $calc = makeCalculator();
    expect($calc->calculateFee('qris', 100_000))->toBe(700);
});

test('[BERHASIL] fee shopeepay 2% dari nominal', function () {
    $calc = makeCalculator();
    expect($calc->calculateFee('shopeepay', 100_000))->toBe(2000);
});

test('[BERHASIL] fee dana 1.5% dari nominal', function () {
    $calc = makeCalculator();
    expect($calc->calculateFee('dana', 200_000))->toBe(3000);
});

test('[BERHASIL] fee ovo 1.5% dari nominal', function () {
    $calc = makeCalculator();
    expect($calc->calculateFee('ovo', 200_000))->toBe(3000);
});

// ── edge cases ──────────────────────────────────────────────

test('[BERHASIL] fee 0 jika metode tidak dikenali', function () {
    $calc = makeCalculator();
    expect($calc->calculateFee('unknown_method', 100_000))->toBe(0);
});

test('[BERHASIL] tarif fee yang diubah admin terefleksi di kalkulasi', function () {
    $calc = makeCalculator('merchant', ['qris' => ['type' => 'percent', 'rate' => 1.0]]);
    expect($calc->calculateFee('qris', 100_000))->toBe(1000);
});
