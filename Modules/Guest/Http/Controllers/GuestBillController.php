<?php

namespace Modules\Guest\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use DomainException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Guest\Http\Requests\PayGuestBillRequest;
use Modules\Guest\Repositories\Contracts\GuestBillRepositoryInterface;
use Modules\Guest\Services\GuestBillingService;
use Modules\Guest\Transformers\GuestBillResource;
use Modules\Rental\Repositories\Contracts\LeaseRepositoryInterface;
use Modules\Rental\Enums\LeaseStatus;
use Modules\Resident\Repositories\Contracts\ResidentRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GuestBillController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly GuestBillingService $billingService,
        private readonly GuestBillRepositoryInterface $billRepository,
        private readonly ResidentRepositoryInterface $residentRepository,
        private readonly LeaseRepositoryInterface $leaseRepository,
    ) {}

    /**
     * Get the bill for a specific guest (validates ownership).
     */
    public function show(int $guestId)
    {
        try {
            // Validate ownership first
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

    /**
     * Process payment for a guest bill.
     */
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

    /**
     * Handle Midtrans webhook notification (no auth required).
     */
    public function midtransNotification(Request $request)
    {
        try {
            $this->billingService->handleMidtransNotification($request->all());

            return response()->json(['message' => 'OK']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error processing notification'], 500);
        }
    }

    /**
     * Validate that the authenticated user owns the guest record.
     */
    private function validateOwnership(int $guestId): void
    {
        $userId   = Auth::id();
        $resident = $this->residentRepository->findByUserId($userId);

        if (!$resident) {
            throw new HttpException(403, 'Anda belum melengkapi biodata penghuni.');
        }

        $lease = $this->leaseRepository->getByResidentId($resident->id)
            ->firstWhere('status', LeaseStatus::ACTIVE);

        if (!$lease) {
            throw new HttpException(403, 'Anda tidak memiliki sewa aktif.');
        }

        // Check that the guest belongs to this lease
        $guest = \Modules\Guest\Models\Guest::find($guestId);

        if (!$guest || $guest->lease_id !== $lease->id) {
            throw new HttpException(403, 'Anda tidak memiliki akses ke data tamu ini.');
        }
    }
}
