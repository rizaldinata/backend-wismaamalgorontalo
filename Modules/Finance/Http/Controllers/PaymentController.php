<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Finance\Http\Requests\PayInvoiceRequest;
use Modules\Finance\Services\FinanceService;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FinanceService $financeService
    ) {}

    public function pay(PayInvoiceRequest $request, int $invoiceId)
    {
        $data = $request->validated();

        if ($request->hasFile('payment_proof')) {
            $data['payment_proof'] = $request->file('payment_proof');
        }

        $payment = $this->financeService->processPayment($invoiceId, $data);

        return $this->apiSuccess($payment, 'Proses pembayaran berhasil diinisialisasi', 201);
    }

    public function verify(Request $request, int $paymentId)
    {
        $request->validat([
            'is_approved' => 'required|boolean',
            'admin_notes' => 'nullable|string',
        ]);

        $payment = $this->financeService->verifyPayment(
            $paymentId,
            $request->is_approved,
            $request->admin_notes,
        );

        $message = $request->is_approved ? 'Pembayaran berhasil diverifikasi. kamar otomatis terisi' : 'Pembayaran ditolak';

        return $this->apiSuccess($payment, $message);
    }
}
