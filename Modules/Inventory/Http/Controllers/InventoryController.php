<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Inventory\Http\Requests\StoreInventoryRequest;
use Modules\Inventory\Http\Requests\UpdateInventoryRequest;
use Modules\Inventory\Repositories\Contracts\InventoryRepositoryInterface;
use Modules\Inventory\Services\InventoryService;
use Modules\Inventory\Transformers\InventoryResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InventoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly InventoryRepositoryInterface $inventoryRepository
    ) {}

    public function index()
    {
        $inventories = $this->inventoryRepository->getAll();
        return $this->apiSuccess(InventoryResource::collection($inventories), 'Data inventory berhasil diambil');
    }

    public function store(StoreInventoryRequest $request)
    {
        $inventory = $this->inventoryService->createInventory($request->validated());

        return $this->apiSuccess(
            new InventoryResource($inventory),
            'Barang berhasil ditambahkan',
            201
        );
    }

    public function show(int $id)
    {
        $inventory = $this->inventoryRepository->findById($id);

        if (!$inventory) {
            throw new NotFoundHttpException('Data barang tidak ditemukan');
        }

        return $this->apiSuccess(new InventoryResource($inventory), 'Detail barang berhasil diambil');
    }

    public function update(UpdateInventoryRequest $request, int $id)
    {
        $inventory = $this->inventoryRepository->findById($id);

        if (!$inventory) {
            throw new NotFoundHttpException('Data barang tidak ditemukan');
        }

        $updatedInventory = $this->inventoryService->updateInventory($inventory, $request->validated());

        return $this->apiSuccess(
            new InventoryResource($updatedInventory),
            'Data barang berhasil diperbarui'
        );
    }

    public function destroy(int $id)
    {
        $inventory = $this->inventoryRepository->findById($id);

        if (!$inventory) {
            throw new NotFoundHttpException('Data barang tidak ditemukan');
        }

        $this->inventoryService->deleteInventory($inventory);

        return $this->apiSuccess(null, 'Data barang berhasil dihapus');
    }
}
