<?php

namespace Modules\Auth\Repositories\Contracts;

interface RoleRepositoryInterface
{
    public function getAll();

    public function getAllExceptPermission(string $permissionName);

    public function getExceptNames(array $names);

    public function findById(int $id);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id);
}
