<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use DomainException;
use Illuminate\Http\Request;
use Modules\Finance\Http\Requests\StoreExpenseRequest;
use Modules\Finance\Http\Requests\UpdateExpenseRequest;
use Modules\Finance\Repositories\Contracts\ExpenseRepositoryInterface;
use Modules\Finance\Services\ExpenseService;
use Modules\Finance\Transformers\ExpenseResource;

class ExpenseController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ExpenseService $expenseService,
        private readonly ExpenseRepositoryInterface $expenseRepository
    ) {}

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:50'
        ]);

        $perPage = (int) $request->query('per_page', 15);

        $expenses = $this->expenseService->getAllExpenses($perPage);

        return ExpenseResource::collection($expenses)->additional([
            'success' => true,
            'message' => 'Daftar pengeluaran berhasil diambil'
        ]);
    }

    public function store(StoreExpenseRequest $request)
    {
        $expense = $this->expenseService->createManualExpense($request->validated());

        return $this->apiSuccess(
            new ExpenseResource($expense),
            'Pengeluaran berhasil dicatat',
            201,
        );
    }

    public function show(int $id)
    {
        $expense = $this->expenseRepository->findOrFail($id);

        return $this->apiSuccess(new ExpenseResource($expense), 'Detail pengeluaran berhasil diambil');
    }

    public function update(UpdateExpenseRequest $request, int $id)
    {
        $expense = $this->expenseRepository->findOrFail($id);

        try {
            $updatedExpense = $this->expenseService->updateManualExpense($expense, $request->validated());
            return $this->apiSuccess(new ExpenseResource($updatedExpense), 'Data pengeluaran berhasil diperbarui');
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    public function destroy(int $id)
    {
        $expense = $this->expenseRepository->findOrFail($id);

        try {
            $this->expenseService->deleteManualExpense($expense);
            return $this->apiSuccess(null, 'Data pengeluaran berhasil dihapus');
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }
}
