<?php

namespace Modules\Rental\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Rental\Http\Requests\StoreLeaseRequest;
use Modules\Rental\Services\LeaseService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;

class LeaseController extends Controller
{
    use ApiResponse;

    protected $leaseService;

    public function __construct(LeaseService $leaseService)
    {
        $this->leaseService = $leaseService;
        // Middleware auth sudah di route group, tapi bisa dipertegas di sini
    }

    public function store(StoreLeaseRequest $request)
    {
        try {
            $lease = $this->leaseService->createLeaseRequest(
                Auth::user(),
                $request->validated(),
                $request->file('payment_proof')
            );

            return $this->apiSuccess($lease, 'Pengajuan sewa berhasil dikirim', 201);
        } catch (\Exception $e) {
            return $this->apiError($e->getMessage(), 400);
        }
    }

    public function myLeases()
    {
        $leases = $this->leaseService->getUserLeases(Auth::id());
        return $this->apiSuccess($leases, 'Data sewa berhasil diambil');
    }

    // Method untuk Admin (Approve/Reject) bisa ditaruh di sini atau AdminLeaseController terpisah
    public function approve($id)
    {
        try {
            $lease = $this->leaseService->approveLease($id);
            return $this->apiSuccess($lease, 'Sewa berhasil disetujui');
        } catch (\Exception $e) {
            return $this->apiError($e->getMessage(), 400);
        }
    }
}
