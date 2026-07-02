<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Auth\Models\User;
use Modules\Inventory\Models\Inventory;
use Modules\Setting\Models\FeatureToggle;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    Permission::firstOrCreate(['name' => 'view-inventory', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'create-inventory', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'update-inventory', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'delete-inventory', 'guard_name' => 'api']);

    $facilityParent = FeatureToggle::firstOrCreate(
        ['key' => 'facility_management'],
        ['name' => 'Fasilitas & Pemeliharaan', 'is_active' => true, 'is_locked' => false],
    );
    FeatureToggle::firstOrCreate(
        ['key' => 'inventory'],
        ['name' => 'Inventaris Wisma', 'is_active' => true, 'is_locked' => false, 'parent_id' => $facilityParent->id],
    );
});

test('[BERHASIL] admin dapat melihat daftar inventaris', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('view-inventory');
    
    Inventory::factory()->create([
        'name' => 'Lemari Kayu',
        'quantity' => 5,
        'condition' => 'good',
        'purchase_price' => 500000
    ]);

    $response = $this->actingAs($admin)->getJson('/api/inventory');

    $response->assertStatus(200)
             ->assertJsonFragment(['name' => 'Lemari Kayu']);
});

test('[BERHASIL] admin dapat menambah inventaris baru', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('create-inventory');

    $response = $this->actingAs($admin)->postJson('/api/inventory', [
        'name' => 'Meja Belajar',
        'quantity' => 10,
        'condition' => 'good',
        'purchase_price' => 200000
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('data.name', 'Meja Belajar');

    $this->assertDatabaseHas('inventories', [
        'name' => 'Meja Belajar',
        'quantity' => 10
    ]);
});

test('[BERHASIL] admin dapat mengubah data inventaris', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('update-inventory');
    
    $inventory = Inventory::factory()->create([
        'name' => 'Kipas Angin',
        'quantity' => 2,
        'condition' => 'good'
    ]);

    $response = $this->actingAs($admin)->putJson("/api/inventory/{$inventory->id}", [
        'name' => 'Kipas Angin Dinding',
        'quantity' => 3,
        'condition' => 'broken'
    ]);

    $response->assertStatus(200)
             ->assertJsonFragment(['name' => 'Kipas Angin Dinding', 'condition' => 'broken']);
});

test('[BERHASIL] admin dapat menghapus data inventaris', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('delete-inventory');
    
    $inventory = Inventory::factory()->create([
        'name' => 'Kasur Busa',
        'quantity' => 1
    ]);

    $response = $this->actingAs($admin)->deleteJson("/api/inventory/{$inventory->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('inventories', [
        'id' => $inventory->id
    ]);
});

test('[GAGAL] request ditolak jika tidak ada permission view-inventory', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->getJson('/api/inventory');

    $response->assertStatus(403);
});
