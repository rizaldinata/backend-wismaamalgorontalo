<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Finance\Transformers\InvoiceResource;

class InvoiceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:50',
            'status' => 'nullable|string'
        ]);

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['status']);

        $invoices = $this->invoiceRepository->getPaginated($perPage, $filters);

        return InvoiceResource::collection($invoices)->additional([
            'success' => true,
            'message' => 'Daftar tagihan berhasil diambil'
        ]);
    }

    public function show(int $id)
    {
        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan tidak ditemukan'
            ], 404);
        }

        $invoice->load(['lease.resident', 'lease.room', 'payments']);

        return $this->apiSuccess(new InvoiceResource($invoice), 'Detail tagihan berhasil diambil');
    }
}
