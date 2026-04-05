<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Http\Requests\StoreRoleRequest;
use Modules\Auth\Http\Requests\UpdateRoleRequest;
use Modules\Auth\Services\RoleService;
use Modules\Auth\Transformers\RoleResource;

class RoleController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly RoleService $roleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $roles = $this->roleService->getAllRoles($request->has('for_user_management'));
        return $this->apiSuccess($roles, 'Daftar Role berhasil diambil');
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->createRole($request->validated());
        return $this->apiSuccess(new RoleResource($role), 'Role berhasil dibuat', 201);
    }

    public function show(int $role): JsonResponse
    {
        $roleData = $this->roleService->getRoleDetails($role);
        return $this->apiSuccess(new RoleResource($roleData), 'Detail Role berhasil diambil');
    }

    public function update(UpdateRoleRequest $request, int $role): JsonResponse
    {
        $updatedRole = $this->roleService->updateRole($role, $request->validated());
        return $this->apiSuccess(new RoleResource($updatedRole), 'Role berhasil diperbarui');
    }

    public function destroy(int $role): JsonResponse
    {
        $this->roleService->deleteRole($role);
        return $this->apiSuccess(null, 'Role berhasil dihapus');
    }
}
