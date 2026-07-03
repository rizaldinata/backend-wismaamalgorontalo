<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Finance\Repositories\Contracts\RefundRequestRepositoryInterface;
use Modules\Finance\Services\FinanceService;
use Modules\Finance\Transformers\RefundRequestResource;

class RefundRequestController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FinanceService $financeService,
        private readonly RefundRequestRepositoryInterface $refundRequestRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        $refundRequests = $this->refundRequestRepository->getPaginated(15, $filters);

        return $this->apiSuccess(
            RefundRequestResource::collectionWithSchedule($refundRequests)->response()->getData(true),
            'Daftar permintaan refund'
        );
    }

    public function proses(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'proof' => 'nullable|file|image|max:5120',
            'admin_fee' => 'nullable|numeric|min:0',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        $refundRequest = $this->financeService->approveRefundRequest(
            id: $id,
            proof: $request->file('proof'),
            adminFee: (float) ($request->input('admin_fee', 0)),
            notes: $request->input('admin_notes', ''),
        );

        return $this->apiSuccess(
            RefundRequestResource::makeWithSchedule($refundRequest),
            'Permintaan pembatalan berhasil diproses'
        );
    }

    public function tolak(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'admin_notes' => 'required|string|max:500',
        ]);

        $refundRequest = $this->financeService->rejectRefundRequest(
            id: $id,
            notes: $request->input('admin_notes'),
        );

        return $this->apiSuccess(
            RefundRequestResource::makeWithSchedule($refundRequest),
            'Permintaan pembatalan ditolak'
        );
    }
}
