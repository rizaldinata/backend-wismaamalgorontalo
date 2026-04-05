<?php

namespace Modules\Auth\Repositories;

use Modules\Auth\Repositories\Contracts\RoleRepositoryInterface;
use Spatie\Permission\Models\Role;

class RoleRepository implements RoleRepositoryInterface
{
    public function getAll()
    {
        return Role::with('permissions:id,name')->get();
    }

    public function getAllExceptPermission(string $permissionName)
    {
        return Role::with('permissions:id,name')
            ->whereDoesntHave('permissions', function ($q) use ($permissionName) {
                $q->where('name', $permissionName);
            })->get();
    }

    public function findById(int $id)
    {
        return Role::with('permissions:id,name')->findOrFail($id);
    }

    public function create(array $data)
    {
        $role = Role::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'guard_name' => 'api',
        ]);

        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->load('permissions');
    }

    public function update(int $id, array $data)
    {
        $role = $this->findById($id);

        $role->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $role->description,
        ]);

        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->load('permissions');
    }

    public function delete(int $id)
    {
        $role = $this->findById($id);
        $role->delete();
        return true;
    }
}
