<?php

namespace Modules\Auth\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\Permission;
use App\Http\Controllers\Controller;

class AdminPermissionController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $permissions = Permission::all();
        return $this->apiSuccess($permissions, 'Daftar permission berhasil diambil');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
            'target' => 'required|in:admin,user',
            'description' => 'nullable|string'
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'target' => $request->target,
            'description' => $request->description,
            'guard_name' => 'api'
        ]);

        return $this->apiSuccess($permission, 'Permission berhasil dibuat', 201);
    }

    public function show($id)
    {
        $permission = Permission::find($id);
        if (!$permission) {
            return $this->apiError('Permission tidak ditemukan', 404);
        }
        return $this->apiSuccess($permission, 'Detail permission berhasil diambil');
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $request->validate([
            'name' => 'required|unique:permissions,name,' . $id,
            'target' => 'required|in:admin,user',
            'description' => 'nullable|string'
        ]);

        $permission->update($request->only(['name', 'target', 'description']));

        return $this->apiSuccess($permission, 'Permission berhasil diperbarui');
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);

        // Detach relationships
        $permission->roles()->detach();

        // Delete permission
        $permission->delete();

        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return $this->apiSuccess(null, 'Permission berhasil dihapus');
    }
}
