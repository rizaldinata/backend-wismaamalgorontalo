<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
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

    public function pay(PayInvoiceRequest $request, int $invoiceId)
    {
        $payment = $this->financeService->processPayment($invoiceId, $request->validated());

        return $this->apiSuccess(
            new PaymentResource($payment),
            'Proses pembayaran berhasil diinisialisasi',
            201
        );
    }

    public function verify(VerifyPaymentRequest $request, int $paymentId)
    {
        $payment = $this->financeService->verifyPayment(
            $paymentId,
            $request->validated('is_approved'),
            $request->validated('admin_notes')
        );

        $message = $request->validated('is_approved')
            ? 'Pembayaran berhasil diverifikasi. Kamar otomatis terisi.'
            : 'Pembayaran ditolak.';

        return $this->apiSuccess(new PaymentResource($payment), $message);
    }
}
