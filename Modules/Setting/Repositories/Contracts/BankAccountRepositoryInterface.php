<?php

namespace Modules\Setting\Repositories\Contracts;

use Modules\Setting\Models\BankAccount;
use Illuminate\Database\Eloquent\Collection;

interface BankAccountRepositoryInterface
{
    public function all(): Collection;

    public function allActive(): Collection;

    public function find(int $id): ?BankAccount;

    public function create(array $data): BankAccount;

    public function update(int $id, array $data): BankAccount;

    public function delete(int $id): void;
}
