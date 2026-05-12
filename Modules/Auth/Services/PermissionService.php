<?php

namespace Modules\Auth\Services;

use Modules\Auth\Repositories\Contracts\PermissionRepositoryInterface;
use Modules\Auth\Repositories\PermissionRepository;
use Spatie\Permission\PermissionRegistrar;

class PermissionService
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository
    ) {}

    public function getAllPermissions()
    {
        return $this->permissionRepository->getAll();
    }

    public function getPermissionDetails(int $id)
    {
        return $this->permissionRepository->findById($id);
    }

    public function createPermission(array $data)
    {
        $permission = $this->permissionRepository->create($data);
        $this->clearPermissionCache();
        return $permission;
    }

    public function updatePermission(int $id, array $data)
    {
        $permission = $this->permissionRepository->update($id, $data);
        $this->clearPermissionCache();
        return $permission;
    }

    public function deletePermission(int $id)
    {
        $this->permissionRepository->delete($id);
        $this->clearPermissionCache();
        return true;
    }

    private function clearPermissionCache(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
