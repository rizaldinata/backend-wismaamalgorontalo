<?php

namespace Modules\Notification\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Notification\Models\NotificationLog;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
    $this->user = User::factory()->create();
});

// ─── GET /api/notification/logs ───────────────────────────────────────────────

test('[BERHASIL] index mengembalikan paginated logs dengan success true', function () {
    NotificationLog::factory()->count(3)->create([
        'type' => 'manual_broadcast',
        'target_phone' => '08123456789',
        'message_body' => 'Pesan test',
        'status' => 'sent',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/notification/logs');

    $response->assertOk()
        ->assertJsonFragment(['success' => true]);
});

test('[BERHASIL] index bisa filter by status', function () {
    NotificationLog::factory()->create(['status' => 'sent', 'type' => 'manual_broadcast', 'target_phone' => '08111', 'message_body' => 'ok']);
    NotificationLog::factory()->create(['status' => 'failed', 'type' => 'manual_broadcast', 'target_phone' => '08222', 'message_body' => 'fail']);

    $response = $this->actingAs($this->user)
        ->getJson('/api/notification/logs?status=sent');

    $response->assertOk();
    $data = $response->json('data.data');
    expect(collect($data)->every(fn ($item) => $item['status'] === 'sent'))->toBeTrue();
});

test('[BERHASIL] index bisa filter by search phone', function () {
    NotificationLog::factory()->create(['target_phone' => '08111222333', 'status' => 'sent', 'type' => 'manual_broadcast', 'message_body' => 'msg']);
    NotificationLog::factory()->create(['target_phone' => '09999999999', 'status' => 'sent', 'type' => 'manual_broadcast', 'message_body' => 'msg']);

    $response = $this->actingAs($this->user)
        ->getJson('/api/notification/logs?search=08111');

    $response->assertOk();
    $data = $response->json('data.data');
    expect($data)->toHaveCount(1);
    expect($data[0]['target_phone'])->toBe('08111222333');
});

// ─── GET /api/notification/logs/summary ───────────────────────────────────────

test('[BERHASIL] summary mengembalikan data ringkasan', function () {
    NotificationLog::factory()->count(2)->create(['status' => 'sent', 'type' => 'manual_broadcast', 'target_phone' => '081', 'message_body' => 'x']);
    NotificationLog::factory()->create(['status' => 'failed', 'type' => 'manual_broadcast', 'target_phone' => '082', 'message_body' => 'y']);

    $response = $this->actingAs($this->user)
        ->getJson('/api/notification/logs/summary');

    $response->assertOk()
        ->assertJsonFragment(['status' => true])
        ->assertJsonStructure(['data' => ['total', 'sent', 'failed', 'today', 'by_type']]);

    expect($response->json('data.total'))->toBe(3);
    expect($response->json('data.sent'))->toBe(2);
    expect($response->json('data.failed'))->toBe(1);
});

// ─── POST /api/notification/logs/{id}/resend ──────────────────────────────────

test('[BERHASIL] resend mengembalikan apiSuccess saat provider berhasil', function () {
    $log = NotificationLog::factory()->create([
        'status' => 'failed',
        'type' => 'manual_broadcast',
        'target_phone' => '08123',
        'message_body' => 'test',
    ]);

    $this->mock(\Modules\Notification\Contracts\WhatsAppProviderInterface::class)
        ->shouldReceive('sendMessage')->once()->andReturn(true)
        ->shouldReceive('getLastError')->andReturn(null);

    $response = $this->actingAs($this->user)
        ->postJson("/api/notification/logs/{$log->id}/resend");

    $response->assertOk()
        ->assertJsonFragment(['status' => true]);
});

test('[GAGAL] resend mengembalikan 403 jika status sudah sent', function () {
    $log = NotificationLog::factory()->create([
        'status' => 'sent',
        'type' => 'manual_broadcast',
        'target_phone' => '08123',
        'message_body' => 'test',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/notification/logs/{$log->id}/resend");

    $response->assertStatus(403);
});

// ─── POST /api/notification/send ──────────────────────────────────────────────

test('[BERHASIL] send dengan target_phone langsung', function () {
    $this->mock(\Modules\Notification\Contracts\WhatsAppProviderInterface::class)
        ->shouldReceive('sendMessage')->once()->andReturn(true)
        ->shouldReceive('getLastError')->andReturn(null);

    $response = $this->actingAs($this->user)
        ->postJson('/api/notification/send', [
            'target_phone' => '08123456789',
            'message_body' => 'Halo, ini pesan test.',
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['status' => true]);
});

test('[GAGAL] send tanpa target_phone dan user_id gagal validasi', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/notification/send', [
            'message_body' => 'Halo test',
        ]);

    $response->assertStatus(422);
});

test('[GAGAL] send dengan message_body melebihi 1000 karakter gagal validasi', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/notification/send', [
            'target_phone' => '08123456789',
            'message_body' => str_repeat('a', 1001),
        ]);

    $response->assertStatus(422);
});

// ─── GET /api/notification/recipients ────────────────────────────────────────

test('[BERHASIL] recipients mengembalikan daftar penerima', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/notification/recipients');

    $response->assertOk()
        ->assertJsonFragment(['status' => true]);
});
