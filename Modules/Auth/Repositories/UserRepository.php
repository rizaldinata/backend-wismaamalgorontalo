<?php

namespace Modules\Auth\Repositories;

use Modules\Auth\Models\User;
use Modules\Auth\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function getAllWithRoles()
    {
        return User::with('roles')->get();
    }

    public function findById(int $id)
    {
        return User::with('roles')->findOrFail($id);
    }

    public function create(array $data)
    {
        return User::create($data);
    }

    public function update(int $id, array $data)
    {
        $user = $this->findById($id);
        $user->update($data);
        return $user;
    }

    public function delete(int $id)
    {
        return User::destroy($id);
    }
}
