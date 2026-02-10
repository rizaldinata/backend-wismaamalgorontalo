<?php

namespace Modules\Resident\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Modules\Resident\Http\Requests\StoreUserRequest;
use Modules\Resident\Http\Requests\UpdateUserRequest;

class AdminUserController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $users = User::with('roles')->latest()->get();
        return $this->apiSuccess($users, 'Daftar pengguna berhasil diambil');
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->ensureRoleIsAllowed($request->role);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        return $this->apiSuccess($user->load('roles'), "Berhasil membuat pengguna baru", 201);
    }

    public function show($id): JsonResponse
    {
        $user = User::with('roles')->findOrFail($id);
        return $this->apiSuccess($user, "Data detail pengguna berhasil diambil");
    }

    public function update(UpdateUserRequest $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($request->has('role')) {
            $this->ensureRoleIsAllowed($request->role);
            $user->syncRoles([$request->role]);
        }

        $user->update($request->only(['name', 'email']));

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return $this->apiSuccess($user->load('roles'), "Berhasil memperbarui data pengguna");
    }

    public function destroy($id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return $this->apiError("Anda tidak dapat menghapus diri sendiri.", 403);
        }

        $user->delete();
        return $this->apiSuccess(null, "Berhasil menghapus pengguna");
    }

    private function ensureRoleIsAllowed($roleName)
    {
        // Cari role secara eksplisit di guard api agar tidak bentrok dengan default 'web'
        $role = Role::where('name', $roleName)
            ->where('guard_name', 'api')
            ->first();

        if (!$role) {
            // Jika role tidak ditemukan sama sekali di database
            abort(422, "Role '$roleName' tidak ditemukan dalam sistem (Guard: api).");
        }

        // Gunakan hasPermissionTo dengan tetap memperhatikan guard
        if ($role->hasPermissionTo('pay_lease_bill', 'api')) {
            abort(422, "Role '$roleName' tidak diizinkan untuk ditetapkan melalui panel admin.");
        }
    }
}
