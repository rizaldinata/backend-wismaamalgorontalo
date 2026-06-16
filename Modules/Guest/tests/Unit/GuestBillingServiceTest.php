<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guest\Enums\GuestBillStatus;
use Modules\Guest\Enums\GuestRelationship;
use Modules\Guest\Models\Guest;
use Modules\Guest\Services\GuestBillingService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// =========================================================
// 6.1 — calculateBilling (logika kalkulasi hari & biaya tamu)
// =========================================================

test('[BERHASIL] calculateBilling menghitung total_days, billable_days, dan charge_amount dengan benar', function () {
    $service = app(GuestBillingService::class);

    // 5 hari total → billable_days = 5 - 2 = 3
    $result = $service->calculateBilling(
        roomPrice: 1000000,
        checkIn: '2026-01-01 12:00:00',
        checkOut: '2026-01-06 12:00:00',
    );

    expect($result['total_days'])->toBe(5);
    expect($result['billable_days'])->toBe(3);
    // charge = 3 * (1_000_000 * 0.05) = 150_000
    expect($result['charge_amount'])->toBe(150000.0);
});

test('[BERHASIL] calculateBilling menghasilkan billable_days 0 jika total_days kurang atau sama dengan 2', function () {
    $service = app(GuestBillingService::class);

    $result = $service->calculateBilling(
        roomPrice: 500000,
        checkIn: '2026-01-01 00:00:00',
        checkOut: '2026-01-02 00:00:00',
    );

    expect($result['total_days'])->toBe(1);
    expect($result['billable_days'])->toBe(0);
    expect($result['charge_amount'])->toBe(0.0);
});

// =========================================================
// 6.2 — createBillIfNeeded
// =========================================================

test('[BERHASIL] createBillIfNeeded membuat tagihan jika charge_amount lebih dari 0', function () {
    $guest = Guest::create([
        'name'         => 'Andi',
        'check_in_at'  => now()->subDays(5),
        'check_out_at' => now(),
        'relationship' => GuestRelationship::FRIEND,
        'total_days'   => 5,
        'billable_days' => 3,
        'charge_amount' => 75000,
    ]);

    $bill = app(GuestBillingService::class)->createBillIfNeeded($guest, 3, 75000);

    expect($bill)->not->toBeNull();
    expect($bill->status)->toBe(GuestBillStatus::UNPAID);
    expect((float) $bill->amount)->toBe(75000.0);
});

test('[BERHASIL] createBillIfNeeded mengembalikan null jika charge_amount adalah 0', function () {
    $guest = Guest::create([
        'name'         => 'Budi',
        'check_in_at'  => now()->subDay(),
        'check_out_at' => now(),
        'relationship' => GuestRelationship::SIBLING,
        'total_days'   => 1,
        'billable_days' => 0,
        'charge_amount' => 0,
    ]);

    $result = app(GuestBillingService::class)->createBillIfNeeded($guest, 0, 0.0);

    expect($result)->toBeNull();
});
