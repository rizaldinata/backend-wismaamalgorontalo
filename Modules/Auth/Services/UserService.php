<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Auth\Repositories\Contracts\UserRepositoryInterface;
use Modules\Auth\Repositories\UserRepository;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository = new UserRepository()
    ) {}

    public function getAllUsers()
    {
        return $this->userRepository->getAllWithRoles();
    }

    public function getUserDetails(int $id)
    {
        return $this->userRepository->findById($id);
    }

    public function createUser(array $data)
    {
        $this->ensureRoleisAllowed($data['role']);

        $data['password'] = Hash::make($data['password']);
        $user = $this->userRepository->create($data);
        $user->assignRole($data['role']);

        return $user->load('roles');
    }

    public function updateUser(int $id, array $data, ?string $roleName = null)
    {
        $user = $this->userRepository->findById($id);

        if ($roleName) {
            $this->ensureRoleIsAllowed($roleName);
            $user->syncRoles($roleName);
        }

        if (isset($data['password']) && filled($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $this->userRepository->update($id, $data)->load('roles');
    }

    public function deleteUser(int $id, int $currentUserId)
    {
        if ($id === $currentUserId) {
            throw new HttpException(403, "Anda tidak dapat menghapus diri sendiri.");
        }

        return $this->userRepository->delete($id);
    }

    private function ensureRoleIsAllowed(string $roleName): void
    {
        $role = Role::where('name', $roleName)->where('guard_name', 'api')->first();

        if (!$role) {
            throw new HttpException(422, "Role '{$roleName}' tidak ditemukan dalam sistem (Guard: api).");
        }

        if ($role->hasPermissionTo('pay_lease_bill', 'api')) {
            throw new HttpException(422, "Role '{$roleName}' tidak diizinkan untuk ditetapkan melalui panel admin.");
        }
    }
}
