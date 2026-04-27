<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Finance\Http\Requests\PayInvoiceRequest;
use Modules\Finance\Http\Requests\VerifyPaymentRequest;
use Modules\Finance\Services\FinanceService;
use Modules\Finance\Repositories\Contracts\PaymentRepositoryInterface;
use Modules\Finance\Transformers\PaymentResource;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FinanceService $financeService,
        private readonly PaymentRepositoryInterface $paymentRepository
    ) {}

    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:50',
            'status' => 'nullable|string',
            'payment_method' => 'nullable|string'
        ]);

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['status', 'payment_method']);

        $payments = $this->paymentRepository->getPaginated($perPage, $filters);

        return PaymentResource::collection($payments)->additional([
            'success' => true,
            'message' => 'Daftar log pembayaran berhasil diambil'
        ]);
    }

    public function show(int $id)
    {
        $payment = $this->paymentRepository->findOrFail($id);
        $payment->load(['invoice.lease.resident', 'invoice.lease.room']);

        return $this->apiSuccess(new PaymentResource($payment), 'Detail pembayaran berhasil diambil');
    }

    public function pay(PayInvoiceRequest $request, int $invoiceId): JsonResponse
    {
        $payment = $this->financeService->processPayment($invoiceId, $request->validated());

        return $this->apiSuccess(
            new PaymentResource($payment),
            'Proses pembayaran berhasil diinisialisasi',
            201
        );
    }

    public function verify(VerifyPaymentRequest $request, int $paymentId): JsonResponse
    {
        $payment = $this->financeService->verifyPayment(
            $paymentId,
            $request->boolean('is_approved'),
            $request->input('admin_notes'),
        );

        $message = $request->boolean('is_approved')
            ? 'Pembayaran berhasil diverifikasi.'
            : 'Pembayaran ditolak.';

        return $this->apiSuccess(new PaymentResource($payment), $message);
    }

    public function refund(Request $request, int $paymentId): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:255'
        ]);

        $payment = $this->financeService->refundPayment($paymentId, $request->input('reason'));

        return $this->apiSuccess(new PaymentResource($payment), 'Dana berhasil dikembalikan');
    }

    public function midtransNotification(Request $request)
    {
        $this->financeService->handleMidtransNotification($request->all());

        return response()->json(['message' => 'Notifikasi diterima'], 200);
    }
}
