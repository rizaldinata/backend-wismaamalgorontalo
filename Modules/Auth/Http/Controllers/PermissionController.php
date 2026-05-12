<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\StorePermissionRequest;
use Modules\Auth\Http\Requests\UpdatePermissionRequest;
use Modules\Auth\Services\PermissionService;
use Modules\Auth\Transformers\PermissionResource;

class PermissionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PermissionService $permissionService
    ) {}

    public function index(): JsonResponse
    {
        $permissions = $this->permissionService->getAllPermissions();
        return $this->apiSuccess(PermissionResource::collection($permissions), 'Daftar permission berhasil diambil');
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = $this->permissionService->createPermission($request->validated());
        return $this->apiSuccess(new PermissionResource($permission), 'Permission berhasil dibuat', 201);
    }

    public function show(int $id): JsonResponse
    {
        $permission = $this->permissionService->getPermissionDetails($id);
        return $this->apiSuccess(new PermissionResource($permission), 'Detail permission berhasil diambil');
    }

    public function update(UpdatePermissionRequest $request, int $id): JsonResponse
    {
        $permission = $this->permissionService->updatePermission($id, $request->validated());
        return $this->apiSuccess(new PermissionResource($permission), 'Permission berhasil diperbarui');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->permissionService->deletePermission($id);
        return $this->apiSuccess(null, 'Permission berhasil dihapus');
    }
}
