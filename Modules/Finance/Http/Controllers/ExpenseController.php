<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Finance\Services\FinanceService;
use Modules\Finance\Transformers\ExpenseResource;

class ExpenseController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FinanceService $financeService
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
}
