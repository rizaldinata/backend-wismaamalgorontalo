<?php

namespace Modules\Setting\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Setting\Models\BankAccount;
use Modules\Setting\Repositories\Contracts\BankAccountRepositoryInterface;

class BankAccountRepository implements BankAccountRepositoryInterface
{
    public function all(): Collection
    {
        return BankAccount::orderBy('sort_order')->orderBy('id')->get();
    }

    public function allActive(): Collection
    {
        return BankAccount::active()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function find(int $id): ?BankAccount
    {
        return BankAccount::find($id);
    }

    public function create(array $data): BankAccount
    {
        return BankAccount::create($data);
    }

    public function update(int $id, array $data): BankAccount
    {
        $account = BankAccount::findOrFail($id);
        $account->update($data);

        return $account->fresh();
    }

    public function delete(int $id): void
    {
        BankAccount::findOrFail($id)->delete();
    }
}
