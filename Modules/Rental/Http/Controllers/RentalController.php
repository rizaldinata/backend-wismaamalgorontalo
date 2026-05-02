<?php

namespace Modules\Rental\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Rental\Http\Requests\StoreLeaseRequest;
use Modules\Rental\Services\RentalService;
use Modules\Rental\Transformers\LeaseResource;

class RentalController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly RentalService $rentalService
    ) {}

    public function index(Request $request)
    {
        $leases = $this->rentalService->getAllLeases($request->all());

        return $this->apiSuccess(LeaseResource::collection($leases), 'Daftar reservasi berhasil diambil.');
    }

    public function store(StoreLeaseRequest $request)
    {
        $lease = $this->rentalService->createLease(Auth::id(), $request->validated());

        return $this->apiSuccess(new LeaseResource($lease), 'Pengajuan sewa berhasil dibuat. Silakan lakukan pembayaran.', 201);
    }

    public function myLeases()
    {
        $leases = $this->rentalService->getMyLeases(Auth::id());

        return $this->apiSuccess(LeaseResource::collection($leases), 'Daftar sewa kamar Anda berhasil diambil.');
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => 'required|string',
        ]);

        $lease = $this->rentalService->updateLeaseStatus($id, $data['status']);

        return $this->apiSuccess(new LeaseResource($lease), 'Status reservasi berhasil diperbarui.');
    }

    public function extend(Request $request, int $id)
    {
        $data = $request->validate([
            'duration' => 'required|integer|min:1|max:12',
        ]);

        $lease = $this->rentalService->extendLease(Auth::id(), $id, $data['duration']);

        return $this->apiSuccess(new LeaseResource($lease), 'Sewa berhasil diperpanjang. Silakan lakukan pembayaran tagihan terbaru.', 201);
    }
}
