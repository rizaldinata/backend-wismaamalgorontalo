<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Finance\Repositories\Contracts\PaymentRepositoryInterface;
use Modules\Finance\Transformers\InvoiceResource;
use Modules\Finance\Transformers\PaymentResource;
use Modules\Resident\Repositories\Contracts\ResidentRepositoryInterface;
use Modules\Rental\Repositories\Contracts\LeaseRepositoryInterface;

class ResidentFinanceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ResidentRepositoryInterface $residentRepository,
        private readonly LeaseRepositoryInterface $leaseRepository
    ) {}

    /**
     * Get financial summary for the logged-in resident.
     */
    public function summary()
    {
        $resident = $this->residentRepository->findByUserId(Auth::id());
        
        if (!$resident) {
            return $this->apiError('Data penghuni tidak ditemukan. Pastikan Anda sudah melengkapi profil.', 404);
        }

        $activeLease = $resident->active_lease;
        
        // Get all unpaid invoices for this resident
        $unpaidInvoices = $this->invoiceRepository->getPaginated(100, [
            'resident_id' => $resident->id,
            'status' => 'unpaid'
        ]);

        $totalUnpaid = collect($unpaidInvoices->items())->sum('amount');
        
        return $this->apiSuccess([
            'resident_name' => $resident->name,
            'active_lease' => $activeLease ? [
                'id' => $activeLease->id,
                'room_number' => $activeLease->room->room_number ?? '-',
                'end_date' => $activeLease->end_date->format('Y-m-d'),
                'rental_type' => is_object($activeLease->rental_type) ? $activeLease->rental_type->value : $activeLease->rental_type,
            ] : null,
            'total_unpaid' => (float) $totalUnpaid,
            'unpaid_count' => $unpaidInvoices->total(),
        ], 'Ringkasan keuangan berhasil diambil');
    }

    /**
     * Get paginated invoices for the logged-in resident.
     */
    public function invoices(Request $request)
    {
        $resident = $this->residentRepository->findByUserId(Auth::id());
        if (!$resident) {
            return $this->apiError('Data penghuni tidak ditemukan.', 404);
        }

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['status']);
        $filters['resident_id'] = $resident->id;

        $invoices = $this->invoiceRepository->getPaginated($perPage, $filters);

        return InvoiceResource::collection($invoices)->additional([
            'success' => true,
            'message' => 'Daftar tagihan Anda berhasil diambil'
        ]);
    }

    /**
     * Get paginated payment history for the logged-in resident.
     */
    public function payments(Request $request)
    {
        $resident = $this->residentRepository->findByUserId(Auth::id());
        if (!$resident) {
            return $this->apiError('Data penghuni tidak ditemukan.', 404);
        }

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['status', 'payment_method']);
        $filters['resident_id'] = $resident->id;

        $payments = $this->paymentRepository->getPaginated($perPage, $filters);

        return PaymentResource::collection($payments)->additional([
            'success' => true,
            'message' => 'Riwayat pembayaran Anda berhasil diambil'
        ]);
    }
}
