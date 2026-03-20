<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Finance\Http\Requests\StoreExpenseRequest;
use Modules\Finance\Repositories\ExpenseRepository;
use Modules\Finance\Services\FinanceService;
use Modules\Finance\Transformers\ExpenseResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExpenseController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FinanceService $financeService,
        private readonly ExpenseRepository $expenseRepository
    ) {}

    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);

        $expenses = $this->financeService->getAllExpenses($perPage);

        return ExpenseResource::collection($expenses)->additional([
            'success' => true,
            'message' => 'Daftar pengeluaran berhasil diambil'
        ]);
    }

    public function store(StoreExpenseRequest $request)
    {
        $expense = $this->financeService->createManualExpense($request->validated());

        return $this->apiSuccess(
            new ExpenseResource($expense),
            'Pengeluaran berhasil dicatat',
            201,
        );
    }

    public function show(int $id)
    {
        $expense = $this->expenseRepository->findById($id);

        if (!$expense) {
            throw new NotFoundHttpException('Data pengeluaran tidak ditemukan');
        }

        return $this->apiSuccess(new ExpenseResource($expense), 'Detail pengeluaran berhasil diambil');
    }

    public function update(StoreExpenseRequest $request, int $id) // Menggunakan request yg sama
    {
        $expense = $this->expenseRepository->findById($id);

        if (!$expense) {
            throw new NotFoundHttpException('Data pengeluaran tidak ditemukan');
        }

        $updatedExpense = $this->financeService->updateManualExpense($expense, $request->validated());

        return $this->apiSuccess(new ExpenseResource($updatedExpense), 'Data pengeluaran berhasil diperbarui');
    }

    public function destroy(int $id)
    {
        $expense = $this->expenseRepository->findById($id);

        if (!$expense) {
            throw new NotFoundHttpException('Data pengeluaran tidak ditemukan');
        }

        $this->financeService->deleteManualExpense($expense);

        return $this->apiSuccess(null, 'Data pengeluaran berhasil dihapus');
    }
}
