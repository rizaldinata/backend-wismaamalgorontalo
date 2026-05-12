<?php

namespace Modules\Finance\Tests\Unit;

use App\Events\Inventory\InventariBaru;
use App\Events\Inventory\InventarisDihapus;
use App\Events\Inventory\InventarisDiperbarui;
use Modules\Finance\Listeners\CatatPengeluaranInventariBaru;
use Modules\Finance\Listeners\HapusPengeluaranInventaris;
use Modules\Finance\Listeners\SinkronisasiPengeluaranInventaris;
use Modules\Finance\Services\ExpenseService;
use Modules\Inventory\Models\Inventory;
use PHPUnit\Framework\TestCase;

class InventoryListenersTest extends TestCase
{
    private ExpenseService $expenseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->expenseService = $this->createMock(ExpenseService::class);
    }

    // --- CatatPengeluaranInventariBaru ---

    public function test_mencatat_pengeluaran_ketika_harga_lebih_dari_nol(): void
    {
        $event = new InventariBaru(
            inventoryId: 5,
            name: 'Kasur Busa',
            quantity: 2,
            purchasePrice: 500000.0,
        );

        $this->expenseService
            ->expects($this->once())
            ->method('recordExpense')
            ->with($this->callback(function ($data) {
                return $data['reference_id'] === 5
                    && $data['amount'] === 500000.0
                    && str_contains($data['title'], 'Kasur Busa')
                    && str_contains($data['description'], '2 unit');
            }));

        $listener = new CatatPengeluaranInventariBaru($this->expenseService);
        $listener->handle($event);
    }

    public function test_tidak_mencatat_pengeluaran_ketika_harga_nol(): void
    {
        $event = new InventariBaru(
            inventoryId: 6,
            name: 'Barang Gratis',
            quantity: 1,
            purchasePrice: 0.0,
        );

        $this->expenseService->expects($this->never())->method('recordExpense');

        $listener = new CatatPengeluaranInventariBaru($this->expenseService);
        $listener->handle($event);
    }

    public function test_reference_type_adalah_inventory_model(): void
    {
        $event = new InventariBaru(
            inventoryId: 7,
            name: 'Lemari',
            quantity: 1,
            purchasePrice: 800000.0,
        );

        $this->expenseService
            ->expects($this->once())
            ->method('recordExpense')
            ->with($this->callback(function ($data) {
                return $data['reference_type'] === Inventory::class;
            }));

        $listener = new CatatPengeluaranInventariBaru($this->expenseService);
        $listener->handle($event);
    }

    // --- SinkronisasiPengeluaranInventaris ---

    public function test_sinkronisasi_pengeluaran_inventaris_memanggil_sync(): void
    {
        $event = new InventarisDiperbarui(
            inventoryId: 3,
            name: 'Kursi Diperbarui',
            purchasePrice: 250000.0,
        );

        $this->expenseService
            ->expects($this->once())
            ->method('syncExpenseByReference')
            ->with(
                3,
                Inventory::class,
                $this->callback(function ($data) {
                    return $data['amount'] === 250000.0
                        && str_contains($data['title'], 'Kursi Diperbarui');
                })
            );

        $listener = new SinkronisasiPengeluaranInventaris($this->expenseService);
        $listener->handle($event);
    }

    // --- HapusPengeluaranInventaris ---

    public function test_hapus_pengeluaran_inventaris_memanggil_remove(): void
    {
        $event = new InventarisDihapus(inventoryId: 9);

        $this->expenseService
            ->expects($this->once())
            ->method('removeExpenseByReference')
            ->with(9, Inventory::class);

        $listener = new HapusPengeluaranInventaris($this->expenseService);
        $listener->handle($event);
    }
}
