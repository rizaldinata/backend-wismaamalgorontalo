<?php

namespace Modules\Auth\Services;

use Modules\Auth\Repositories\Contracts\RoleRepositoryInterface;
use Modules\Auth\Repositories\RoleRepository;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoleService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository
    ) {}

    public function getAllRoles(bool $forUserManagament = false)
    {
        if ($forUserManagament) {
            return $this->roleRepository->getAllExceptPermission('pay_lease_bill');
        }

        return $this->roleRepository->getAll();
    }

    public function getRoleDetails(int $id)
    {
        return $this->roleRepository->findById($id);
    }

    public function createRole(array $data)
    {
        return $this->roleRepository->create($data);
    }

    public function updateRole(int $id, array $data)
    {
        return $this->roleRepository->update($id, $data);
    }

    public function deleteRole(int $id)
    {
        $role = $this->roleRepository->findById($id);

        if ($role->name === 'super-admin') {
            throw new HttpException(403, 'Role admin utama tidak dapat dihapus');
        }

        return $this->roleRepository->delete($id);
    }
}
