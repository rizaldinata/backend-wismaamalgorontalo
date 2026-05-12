<?php

namespace Modules\Auth\Repositories\Contracts;

interface UserRepositoryInterface
{
    public function getAllWithRoles();
    public function findById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}
