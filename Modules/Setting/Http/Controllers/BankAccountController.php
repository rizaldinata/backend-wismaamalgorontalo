<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Setting\Http\Requests\StoreBankAccountRequest;
use Modules\Setting\Http\Requests\UpdateBankAccountRequest;
use Modules\Setting\Repositories\Contracts\BankAccountRepositoryInterface;

class BankAccountController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly BankAccountRepositoryInterface $repository
    ) {}

    public function index(): JsonResponse
    {
        $accounts = $this->repository->all();

        return $this->apiSuccess($accounts, 'Daftar rekening bank berhasil dimuat');
    }

    public function publicIndex(): JsonResponse
    {
        $accounts = $this->repository->allActive();

        return $this->apiSuccess($accounts, 'Daftar rekening bank aktif berhasil dimuat');
    }

    public function store(StoreBankAccountRequest $request): JsonResponse
    {
        $account = $this->repository->create($request->validated());

        return $this->apiSuccess($account, 'Rekening bank berhasil ditambahkan', 201);
    }

    public function update(UpdateBankAccountRequest $request, int $id): JsonResponse
    {
        $account = $this->repository->update($id, $request->validated());

        return $this->apiSuccess($account, 'Rekening bank berhasil diperbarui');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->apiSuccess(null, 'Rekening bank berhasil dihapus');
    }
}
