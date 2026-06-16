<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Http\Requests\PerpanjangSewaRequest;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Finance\Repositories\Contracts\PaymentRepositoryInterface;
use Modules\Finance\Services\FinanceService;
use Modules\Finance\Transformers\InvoiceResource;
use Modules\Finance\Transformers\PaymentResource;

class ResidentFinanceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly FinanceService $financeService,
    ) {}

    public function summary()
    {
        $userId = Auth::id();

        $unpaidInvoices = $this->invoiceRepository->getPaginated(100, [
            'tenant_user_id' => $userId,
            'status' => 'unpaid',
        ]);

        // Exclude invoice yang payment-nya sudah failed — user harus retry lewat alur baru
        $invoiceIds = collect($unpaidInvoices->items())->pluck('id');
        $failedInvoiceIds = DB::table('payments')
            ->whereIn('invoice_id', $invoiceIds)
            ->where('status', 'failed')
            ->pluck('invoice_id')
            ->flip();

        $billableInvoices = collect($unpaidInvoices->items())
            ->reject(fn ($inv) => $failedInvoiceIds->has($inv->id));

        $totalUnpaid = $billableInvoices->sum('amount');

        $activeTenants = DB::table('finance_active_tenants')
            ->where('user_id', $userId)
            ->get();

        $activeLeases = $activeTenants->map(fn ($t) => [
            'id'          => $t->schedule_id,
            'room_number' => $t->room_number ?? '-',
            'end_date'    => $t->end_date,
            'rental_type' => 'monthly',
        ])->values()->toArray();

        return $this->apiSuccess([
            'resident_name' => Auth::user()->name,
            'active_leases' => $activeLeases,
            'total_unpaid'  => (float) $totalUnpaid,
            'unpaid_count'  => $billableInvoices->count(),
        ], 'Ringkasan keuangan berhasil diambil');
    }

    public function invoices(Request $request)
    {
        $userId = Auth::id();
        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['status']);
        $filters['tenant_user_id'] = $userId;

        if ($request->has('schedule_id')) {
            $filters['schedule_ids'] = [(int) $request->query('schedule_id')];
        }

        $invoices = $this->invoiceRepository->getPaginated($perPage, $filters);

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

    public function perpanjangSewa(PerpanjangSewaRequest $request, int $scheduleId): JsonResponse
    {
        $userId = Auth::id();

        // Validasi kepemilikan: schedule harus milik user yang login
        $activeTenant = DB::table('finance_active_tenants')
            ->where('user_id', $userId)
            ->where('schedule_id', $scheduleId)
            ->first();

        if (! $activeTenant) {
            return $this->apiError('Sewa aktif tidak ditemukan.', 404);
        }

        // Load jadwal untuk mendapatkan agreed_price dan end_date
        $schedule = DB::table('room_schedules')->where('id', $scheduleId)->first();

        if (! $schedule || ! $schedule->agreed_price || (float) $schedule->agreed_price <= 0) {
            return $this->apiError('Harga sewa belum diatur. Hubungi admin.', 422);
        }

        $durationMonths = $request->integer('duration_months');
        $currentEndDate = Carbon::parse($schedule->end_date);
        $newEndDate     = $currentEndDate->copy()->addMonths($durationMonths);
        $amount         = (float) $schedule->agreed_price * $durationMonths;
        $suffix         = strtoupper(substr(md5(uniqid()), 0, 6));
        $invoiceNumber  = 'EXT-' . date('Ymd') . '-' . str_pad($scheduleId, 4, '0', STR_PAD_LEFT) . '-' . $suffix;

        // Buat invoice untuk periode perpanjangan
        $invoice = $this->invoiceRepository->create([
            'schedule_id'    => $scheduleId,
            'invoice_number' => $invoiceNumber,
            'amount'         => $amount,
            'status'         => InvoiceStatus::UNPAID->value,
            'due_date'       => now()->toDateString(),
            'tenant_user_id' => $userId,
            'tenant_name'    => $activeTenant->tenant_name,
            'room_number'    => $activeTenant->room_number,
            'period_start'   => $currentEndDate->copy()->addDay()->toDateString(),
            'period_end'     => $newEndDate->toDateString(),
        ]);

        // Perpanjang end_date jadwal dan snapshot finance_active_tenants
        DB::table('room_schedules')->where('id', $scheduleId)->update([
            'end_date'   => $newEndDate->toDateString(),
            'updated_at' => now(),
        ]);

        DB::table('finance_active_tenants')->where('schedule_id', $scheduleId)->update([
            'end_date'   => $newEndDate->toDateString(),
            'updated_at' => now(),
        ]);

        // Proses pembayaran
        $payment = $this->financeService->processPayment($invoice->id, $request->validated());

        return $this->apiSuccess(
            new PaymentResource($payment),
            'Perpanjangan sewa berhasil diproses',
            201
        );
    }
}
