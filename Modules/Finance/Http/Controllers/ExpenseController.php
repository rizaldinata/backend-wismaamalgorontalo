<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
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

    /**
     * Daftar Pengeluaran (Expense)
     *
     * Melihat semua pengeluaran keuangan wisma, baik dari maintenance rutin maupun pengeluaran manual. (Hanya Admin)
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $perPage = (int) $request->query('per_page', 50);

        $expenses = $this->expenseService->getAllExpenses($perPage);

        return ExpenseResource::collection($expenses)->additional([
            'success' => true,
            'message' => 'Daftar pengeluaran berhasil diambil',
        ]);
    }

    /**
     * Catat Pengeluaran Manual
     *
     * Menambahkan data pengeluaran secara manual (di luar jadwal maintenance/pengeluaran tetap). (Hanya Admin)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreExpenseRequest $request)
    {
        $expense = $this->expenseService->createManualExpense($request->validated());

        return $this->apiSuccess(
            new ExpenseResource($expense),
            'Pengeluaran berhasil dicatat',
            201,
        );
    }

    /**
     * Detail Pengeluaran
     *
     * Melihat detail dari satu transaksi pengeluaran. (Hanya Admin)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $expense = $this->expenseRepository->findOrFail($id);

        return $this->apiSuccess(new ExpenseResource($expense), 'Detail pengeluaran berhasil diambil');
    }

    /**
     * Edit Pengeluaran Manual
     *
     * Mengubah nominal atau deskripsi pada pengeluaran manual. (Hanya Admin)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateExpenseRequest $request, int $id)
    {
        $expense = $this->expenseRepository->findOrFail($id);

        $updatedExpense = $this->expenseService->updateManualExpense($expense, $request->validated());

        return $this->apiSuccess(new ExpenseResource($updatedExpense), 'Data pengeluaran berhasil diperbarui');
    }

    /**
     * Hapus Pengeluaran Manual
     *
     * Menghapus transaksi pengeluaran manual. (Hanya Admin)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $expense = $this->expenseRepository->findOrFail($id);

        $this->expenseService->deleteManualExpense($expense);

        return $this->apiSuccess(null, 'Data pengeluaran berhasil dihapus');
    }
}
