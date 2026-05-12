<?php

use App\Events\Inventory\InventariBaru;
use App\Events\Inventory\InventarisDihapus;
use App\Events\Inventory\InventarisDiperbarui;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Inventory\Models\Inventory;
use Modules\Inventory\Repositories\Contracts\InventoryRepositoryInterface;
use Modules\Inventory\Services\InventoryService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ----- Task 3.6: fitur inventaris masih berfungsi normal -----

test('membuat inventaris dengan purchase_price memicu event InventariBaru', function () {
    Event::fake();

    $service = app(InventoryService::class);
    $service->createInventory([
        'name' => 'Kursi Kayu',
        'quantity' => 2,
        'condition' => 'good',
        'purchase_price' => 350000,
    ]);

    Event::assertDispatched(InventariBaru::class, function ($event) {
        return $event->name === 'Kursi Kayu'
            && $event->quantity === 2
            && $event->purchasePrice === 350000.0;
    });
});

test('membuat inventaris tanpa purchase_price tidak memicu event InventariBaru', function () {
    Event::fake();

    $service = app(InventoryService::class);
    $service->createInventory([
        'name' => 'Meja',
        'quantity' => 1,
        'condition' => 'good',
    ]);

    Event::assertNotDispatched(InventariBaru::class);
});

test('membuat inventaris dengan purchase_price nol tidak memicu event InventariBaru', function () {
    Event::fake();

    $service = app(InventoryService::class);
    $service->createInventory([
        'name' => 'Kipas',
        'quantity' => 1,
        'condition' => 'good',
        'purchase_price' => 0,
    ]);

    Event::assertNotDispatched(InventariBaru::class);
});

test('memperbarui inventaris dengan purchase_price memicu event InventarisDiperbarui', function () {
    Event::fake();

    $inventory = Inventory::factory()->withPurchasePrice(200000)->create();
    $service = app(InventoryService::class);

    $service->updateInventory($inventory, [
        'name' => 'Kursi Baru',
        'purchase_price' => 450000,
    ]);

    Event::assertDispatched(InventarisDiperbarui::class, function ($event) use ($inventory) {
        return $event->inventoryId === $inventory->id
            && $event->purchasePrice === 450000.0;
    });
});

test('memperbarui inventaris tanpa purchase_price tidak memicu event InventarisDiperbarui', function () {
    Event::fake();

    $inventory = Inventory::factory()->create();
    $service = app(InventoryService::class);

    $service->updateInventory($inventory, ['name' => 'Nama Baru']);

    Event::assertNotDispatched(InventarisDiperbarui::class);
});

test('menghapus inventaris memicu event InventarisDihapus dengan id yang benar', function () {
    Event::fake();

    $inventory = Inventory::factory()->withPurchasePrice(100000)->create();
    $id = $inventory->id;
    $service = app(InventoryService::class);

    $service->deleteInventory($inventory);

    Event::assertDispatched(InventarisDihapus::class, function ($event) use ($id) {
        return $event->inventoryId === $id;
    });
});

test('inventaris terhapus dari database setelah deleteInventory', function () {
    Event::fake();

    $inventory = Inventory::factory()->create();
    $id = $inventory->id;
    $service = app(InventoryService::class);

    $service->deleteInventory($inventory);

    expect(Inventory::find($id))->toBeNull();
});

// ----- Task 3.7: InventoryService tidak bergantung pada Finance module -----

test('InventoryService tidak membutuhkan Finance module untuk diinstansiasi', function () {
    $reflection = new ReflectionClass(InventoryService::class);
    $params = $reflection->getConstructor()->getParameters();

    $paramTypes = array_map(
        fn($p) => $p->getType()?->getName() ?? '',
        $params
    );

    foreach ($paramTypes as $type) {
        expect($type)->not->toContain('Finance');
    }

    expect($params)->toHaveCount(1);
    expect($paramTypes[0])->toBe(InventoryRepositoryInterface::class);
});

test('InventoryService dapat digunakan tanpa Finance module aktif', function () {
    Event::fake();

    $service = app(InventoryService::class);

    $inventory = $service->createInventory([
        'name' => 'Barang Test',
        'quantity' => 1,
        'condition' => 'good',
        'purchase_price' => 100000,
    ]);

    expect($inventory)->toBeInstanceOf(Inventory::class);
    expect($inventory->name)->toBe('Barang Test');
    Event::assertDispatched(InventariBaru::class);
});
