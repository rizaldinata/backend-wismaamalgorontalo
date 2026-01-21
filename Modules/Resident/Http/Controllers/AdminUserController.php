<?php

namespace Modules\Resident\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Modules\Resident\Http\Requests\StoreUserRequest;
use Modules\Resident\Http\Requests\UpdateUserRequest;

class AdminUserController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $this->authorizePermission('view_users');

        $users = User::with('roles')->latest()->get();

        return $this->apiSuccess($users, 'Daftar pengguna berhasil diambil');
    }

    public function store(StoreUserRequest $request)
    {
        $this->ensureRoleIsAllowed($request->role);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $role = Role::findByName($request->role, 'web');
        $user->assignRole($role);

        return $this->apiSuccess($user, "Berhasil membuat pengguna baru");
    }

    public function show($id)
    {
        $this->authorizePermission('view_users');

        $user = User::with('roles')->findOrFail($id);

        return $this->apiSuccess($user, "Data detail pengguna berhasil diambil");
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::findOrFail($id);

        $this->ensureRoleIsAllowed($request->role);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $role = Role::findByName($request->role, 'web');
        $user->syncRoles([$role]);

        return $this->apiSuccess($user, "Berhasil memperbarui data pengguna");
    }

    public function destroy($id)
    {
        $this->authorizePermission('delete_users');

        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return $this->apiError("Anda tidak dapat menghapus diri sendiri.", 403);
        }

        $user->delete();

        return $this->apiSuccess(null, "Berhasil menghapus pengguna");
    }

    public function getRoles()
    {
        $roles = Role::where('guard_name', 'web')
            ->whereDoesntHave('permissions', function ($query) {
                $query->where('name', 'pay_lease_bill');
            })->get();

        return $this->apiSuccess($roles, "Daftar role berhasil diambil");
    }

    private function authorizePermission($permission)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user || !$user->can($permission)) {
            abort(403, 'Anda tidak memiliki izin untuk melakukan aksi ini.');
        }
    }

    private function ensureRoleIsAllowed($roleName)
    {
        $role = Role::findByName($roleName, 'web');
        if ($role->hasPermissionTo('pay_lease_bill')) {
            abort(422, "Role '$roleName' tidak diizinkan untuk ditetapkan melalui panel admin.");
        }
    }
}
