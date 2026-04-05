<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Finance\Http\Requests\PayInvoiceRequest;
use Modules\Finance\Http\Requests\VerifyPaymentRequest;
use Modules\Finance\Services\FinanceService;
use Modules\Finance\Transformers\PaymentResource;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FinanceService $financeService
    ) {}

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

    public function midtransNotification(Request $request)
    {
        $this->financeService->handleMidtransNotification($request->all());

        return response()->json(['message' => 'Notifikasi diterima'], 200);
    }
}
