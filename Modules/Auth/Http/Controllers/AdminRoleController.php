<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Http\JsonResponse;

class AdminRoleController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Role::with('permissions:id,name');

        // Logic Clean: Jika request datang dari User Management, filter role yang bersifat internal/resident
        if ($request->has('for_user_management')) {
            $query->whereDoesntHave('permissions', function ($q) {
                $q->where('name', 'pay_lease_bill');
            });
        }

        $roles = $query->get();
        return $this->apiSuccess($roles, 'Daftar Role berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'description' => $request->description,
            'guard_name' => 'api'
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return $this->apiSuccess($role->load('permissions'), 'Role berhasil dibuat', 201);
    }

    public function show(Role $role): JsonResponse
    {
        return $this->apiSuccess($role->load('permissions'), 'Detail Role berhasil diambil');
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        $role->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return $this->apiSuccess($role->load('permissions'), 'Role berhasil diperbarui');
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->name === 'super-admin') {
            return $this->apiError('Role admin utama tidak dapat dihapus', 403);
        }

        $role->delete();
        return $this->apiSuccess(null, 'Role berhasil dihapus');
    }
}
