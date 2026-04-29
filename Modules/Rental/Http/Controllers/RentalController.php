<?php

namespace Modules\Rental\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Rental\Http\Requests\StoreLeaseRequest;
use Modules\Rental\Services\RentalService;
use Modules\Rental\Transformers\LeaseResource;

class RentalController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly RentalService $rentalService
    ) {
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

    public function index()
    {
        $rentals = $this->rentalService->getAllRentals();

        return $this->apiSuccess(
            $rentals,
            'Data reservasi berhasil diambil'
        );
    }
}
