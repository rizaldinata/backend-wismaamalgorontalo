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

    /**
     * Daftar Inventaris
     *
     * Melihat semua daftar barang inventaris (Hanya Admin).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $perPage = request()->get('per_page', 10);
        $inventories = $this->inventoryRepository->getPaginated($perPage);
        $resource = InventoryResource::collection($inventories)->response()->getData(true);

        return $this->apiSuccess($resource['data'], 'Data inventory berhasil diambil', 200, $resource['meta']);
    }

    /**
     * Tambah Inventaris
     *
     * Mendaftarkan barang inventaris baru. (Hanya Admin).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreInventoryRequest $request)
    {
        $inventory = $this->inventoryService->createInventory($request->validated());

        return $this->apiSuccess(
            new InventoryResource($inventory),
            'Barang berhasil ditambahkan',
            201
        );
    }

    /**
     * Detail Inventaris
     *
     * Melihat detail spesifik suatu barang inventaris. (Hanya Admin).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $inventory = $this->inventoryRepository->findById($id);

        if (! $inventory) {
            throw new NotFoundHttpException('Data barang tidak ditemukan');
        }

        return $this->apiSuccess(new InventoryResource($inventory), 'Detail barang berhasil diambil');
    }

    /**
     * Update Inventaris
     *
     * Mengubah data barang inventaris. (Hanya Admin).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateInventoryRequest $request, int $id)
    {
        $inventory = $this->inventoryRepository->findById($id);

        if (! $inventory) {
            throw new NotFoundHttpException('Data barang tidak ditemukan');
        }

        $updatedInventory = $this->inventoryService->updateInventory($inventory, $request->validated());

        return $this->apiSuccess(
            new InventoryResource($updatedInventory),
            'Data barang berhasil diperbarui'
        );
    }

    /**
     * Hapus Inventaris
     *
     * Menghapus barang inventaris. (Hanya Admin).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $inventory = $this->inventoryRepository->findById($id);

        if (! $inventory) {
            throw new NotFoundHttpException('Data barang tidak ditemukan');
        }

        $this->inventoryService->deleteInventory($inventory);

        return $this->apiSuccess(null, 'Data barang berhasil dihapus');
    }
}
