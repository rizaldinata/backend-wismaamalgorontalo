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
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;

class ResidentFinanceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ScheduleRepositoryInterface $scheduleRepository,
    ) {}

    public function summary()
    {
        $userId    = Auth::id();
        $schedules = collect($this->scheduleRepository->getByTenantUserId($userId));

        if ($schedules->isEmpty()) {
            return $this->apiSuccess([
                'resident_name' => Auth::user()->name,
                'active_lease'  => null,
                'total_unpaid'  => 0.0,
                'unpaid_count'  => 0,
            ], 'Ringkasan keuangan berhasil diambil');
        }

        $scheduleIds    = $schedules->pluck('id')->toArray();
        $activeSchedule = $schedules->first(fn ($s) => $s->status === ScheduleStatus::ACTIVE);

        $unpaidInvoices = $this->invoiceRepository->getPaginated(100, [
            'schedule_ids' => $scheduleIds,
            'status'       => 'unpaid',
        ]);

        $totalUnpaid = collect($unpaidInvoices->items())->sum('amount');

        $activeLease = null;
        if ($activeSchedule) {
            $rentalType = 'monthly';
            if ($activeSchedule->legacy_lease_id) {
                $rentalType = DB::table('leases')
                    ->where('id', $activeSchedule->legacy_lease_id)
                    ->value('rental_type') ?? 'monthly';
            }

            $room = DB::table('rooms')->where('id', $activeSchedule->room_id)->first();

            $activeLease = [
                'id'          => $activeSchedule->id,
                'room_number' => $room?->number ?? '-',
                'end_date'    => $activeSchedule->end_date->format('Y-m-d'),
                'rental_type' => $rentalType,
            ];
        }

        return $this->apiSuccess([
            'resident_name' => Auth::user()->name,
            'active_lease'  => $activeLease,
            'total_unpaid'  => (float) $totalUnpaid,
            'unpaid_count'  => $unpaidInvoices->total(),
        ], 'Ringkasan keuangan berhasil diambil');
    }

    public function invoices(Request $request)
    {
        $userId      = Auth::id();
        $scheduleIds = collect($this->scheduleRepository->getByTenantUserId($userId))
            ->pluck('id')
            ->toArray();

        if (empty($scheduleIds)) {
            return $this->apiError('Data penghuni tidak ditemukan.', 404);
        }

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['status']);
        $filters['schedule_ids'] = $scheduleIds;

        $invoices = $this->invoiceRepository->getPaginated($perPage, $filters);

        return InvoiceResource::collection($invoices)->additional([
            'success' => true,
            'message' => 'Daftar tagihan Anda berhasil diambil',
        ]);
    }

    public function showInvoice(int $id)
    {
        $userId      = Auth::id();
        $scheduleIds = collect($this->scheduleRepository->getByTenantUserId($userId))
            ->pluck('id')
            ->toArray();

        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice || !in_array($invoice->schedule_id, $scheduleIds)) {
            return $this->apiError('Invoice tidak ditemukan.', 404);
        }

        return (new InvoiceResource($invoice))->additional([
            'success' => true,
            'message' => 'Detail tagihan berhasil diambil',
        ]);
    }

    public function payments(Request $request)
    {
        $userId      = Auth::id();
        $scheduleIds = collect($this->scheduleRepository->getByTenantUserId($userId))
            ->pluck('id')
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
