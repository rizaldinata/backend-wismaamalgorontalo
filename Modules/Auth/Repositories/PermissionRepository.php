<?php

namespace Modules\Auth\Repositories;

use Modules\Auth\Models\Permission;
use Modules\Auth\Repositories\Contracts\PermissionRepositoryInterface;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function getAll()
    {
        return Permission::all();
    }

    public function findById(int $id)
    {
        return Permission::findOrFail($id);
    }

    public function create(array $data)
    {
        return Permission::create([
            'name' => $data['name'],
            'target' => $data['target'],
            'description' => $data['description'] ?? null,
            'guardf_name' => 'api',
        ]);
    }

    public function update(int $id, array $data)
    {
        $permission = $this->findById($id);

        $permission->update([
            'name' => $data['name'] ?? $permission->name,
            'target' => $data['target'] ?? $permission->target,
            'description' => $data['description'] ?? $permission->description,
        ]);
    }

    public function delete(int $id)
    {
        $permission = $this->findById($id);
        $permission->roles()->detach();
        $permission->delete();
        return true;
    }
}
