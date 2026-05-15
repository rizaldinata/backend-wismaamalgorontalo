<?php

namespace Modules\Guest\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use DomainException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Guest\Http\Requests\PayGuestBillRequest;
use Modules\Guest\Models\Guest;
use Modules\Guest\Models\GuestActiveContext;
use Modules\Guest\Repositories\Contracts\GuestBillRepositoryInterface;
use Modules\Guest\Services\GuestBillingService;
use Modules\Guest\Transformers\GuestBillResource;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GuestBillController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly GuestBillingService $billingService,
        private readonly GuestBillRepositoryInterface $billRepository,
    ) {}

    public function show(int $guestId)
    {
        try {
            $this->validateOwnership($guestId);

            $bill = $this->billRepository->findByGuestId($guestId);

            if (!$bill) {
                return $this->apiError('Tagihan tidak ditemukan untuk tamu ini.', 404);
            }

            return $this->apiSuccess(new GuestBillResource($bill), 'Data tagihan berhasil diambil.');
        } catch (HttpException $e) {
            return $this->apiError($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }

    public function pay(PayGuestBillRequest $request, int $guestId)
    {
        try {
            $userId = Auth::id();
            $method = $request->input('payment_method');

            if ($method === 'manual') {
                $bill = $this->billingService->payManual($guestId, $userId, $request->file('payment_proof'));
            } else {
                $bill = $this->billingService->payMidtrans($guestId, $userId);
            }

            return $this->apiSuccess(new GuestBillResource($bill), 'Pembayaran berhasil diproses.');
        } catch (NotFoundHttpException $e) {
            return $this->apiError($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->apiError($e->getMessage(), $e->getStatusCode());
        } catch (DomainException $e) {
            return $this->apiError($e->getMessage(), 422);
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }

    public function midtransNotification(Request $request)
    {
        try {
            $this->billingService->handleMidtransNotification($request->all());

            return response()->json(['message' => 'OK']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error processing notification'], 500);
        }
    }

    private function validateOwnership(int $guestId): void
    {
        $userId  = Auth::id();
        $context = GuestActiveContext::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$context) {
            throw new HttpException(403, 'Anda tidak memiliki sewa aktif.');
        }

        $guest = Guest::find($guestId);

        // Support both legacy (lease_id) and new (schedule_reference_id) linkage
        $belongsToUser = ($context->schedule_id && $guest?->schedule_reference_id === $context->schedule_id)
            || ($context->lease_id && $guest?->lease_id === $context->lease_id);

        if (!$guest || !$belongsToUser) {
            throw new HttpException(403, 'Anda tidak memiliki akses ke data tamu ini.');
        }
    }
}
