<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Finance\Repositories\Contracts\PaymentRepositoryInterface;
use Modules\Finance\Transformers\InvoiceResource;
use Modules\Finance\Transformers\PaymentResource;

class ResidentFinanceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
    ) {}

    public function summary()
    {
        $userId = Auth::id();

        $unpaidInvoices = $this->invoiceRepository->getPaginated(100, [
            'tenant_user_id' => $userId,
            'status' => 'unpaid',
        ]);

        $totalUnpaid = collect($unpaidInvoices->items())->sum('amount');

        // Semua data diambil dari tabel milik Finance sendiri — tidak query modul lain
        $activeTenant = DB::table('finance_active_tenants')
            ->where('user_id', $userId)
            ->first();

        $activeLease = null;
        if ($activeTenant) {
            $activeLease = [
                'id' => $activeTenant->schedule_id,
                'room_number' => $activeTenant->room_number ?? '-',
                'end_date' => $activeTenant->end_date,
                'rental_type' => 'monthly',
            ];
        }

        return $this->apiSuccess([
            'resident_name' => Auth::user()->name,
            'active_lease' => $activeLease,
            'total_unpaid' => (float) $totalUnpaid,
            'unpaid_count' => $unpaidInvoices->total(),
        ], 'Ringkasan keuangan berhasil diambil');
    }

    public function invoices(Request $request)
    {
        $userId = Auth::id();
        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['status']);
        $filters['tenant_user_id'] = $userId;

        $invoices = $this->invoiceRepository->getPaginated($perPage, $filters);

        if ($invoices->isEmpty()) {
            return $this->apiError('Data penghuni tidak ditemukan.', 404);
        }

        return InvoiceResource::collection($invoices)->additional([
            'success' => true,
            'message' => 'Daftar tagihan Anda berhasil diambil',
        ]);
    }

    public function showInvoice(int $id)
    {
        $userId = Auth::id();
        $invoice = $this->invoiceRepository->findById($id);

        if (! $invoice || $invoice->tenant_user_id !== $userId) {
            return $this->apiError('Invoice tidak ditemukan.', 404);
        }

        return (new InvoiceResource($invoice))->additional([
            'success' => true,
            'message' => 'Detail tagihan berhasil diambil',
        ]);
    }

    public function payments(Request $request)
    {
        $userId = Auth::id();

        $unpaidInvoices = $this->invoiceRepository->getPaginated(200, [
            'tenant_user_id' => $userId,
        ]);

        $scheduleIds = collect($unpaidInvoices->items())
            ->pluck('schedule_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($scheduleIds)) {
            return $this->apiError('Data penghuni tidak ditemukan.', 404);
        }

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['status', 'payment_method']);
        $filters['schedule_ids'] = $scheduleIds;

        $payments = $this->paymentRepository->getPaginated($perPage, $filters);

        return PaymentResource::collection($payments)->additional([
            'success' => true,
            'message' => 'Riwayat pembayaran Anda berhasil diambil',
        ]);
    }
}
