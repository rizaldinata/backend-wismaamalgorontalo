<?php

namespace Modules\Guest\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Modules\Guest\Http\Requests\VerifyGuestBillRequest;
use Modules\Guest\Repositories\Contracts\GuestBillRepositoryInterface;
use Modules\Guest\Services\GuestBillingService;
use Modules\Guest\Transformers\AdminGuestBillResource;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminGuestBillController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly GuestBillRepositoryInterface $billRepository,
        private readonly GuestBillingService $billingService,
    ) {}

    /**
     * Paginated list of all guest bills with search support.
     */
    public function index(Request $request)
    {
        try {
            $bills = $this->billRepository->getAllPaginated($request->all());

            return $this->apiSuccess(
                AdminGuestBillResource::collection($bills)->response()->getData(true),
                'Daftar tagihan tamu berhasil diambil.'
            );
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }

    /**
     * Admin verifies or rejects a pending guest bill.
     */
    public function verify(VerifyGuestBillRequest $request, int $id)
    {
        try {
            $bill = $this->billingService->verifyBill(
                $id,
                (bool) $request->input('is_approved'),
                $request->input('admin_notes')
            );

            return $this->apiSuccess(new AdminGuestBillResource($bill), 'Tagihan berhasil diverifikasi.');
        } catch (NotFoundHttpException $e) {
            return $this->apiError($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->apiError($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }
}
