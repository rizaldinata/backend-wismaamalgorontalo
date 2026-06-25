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
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Http\Requests\BayarDendaRequest;
use Modules\Finance\Http\Requests\InitiatePerpanjangSewaRequest;
use Modules\Finance\Http\Requests\PerpanjangSewaRequest;
use Modules\Finance\Repositories\Contracts\FineRepositoryInterface;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Finance\Repositories\Contracts\PaymentRepositoryInterface;
use Modules\Finance\Repositories\Contracts\RefundRequestRepositoryInterface;
use Modules\Finance\Services\FinanceService;
use Modules\Finance\Services\FineService;
use Modules\Finance\Transformers\FineResource;
use Modules\Finance\Transformers\InvoiceResource;
use Modules\Finance\Transformers\PaymentResource;
use Modules\Finance\Transformers\RefundRequestResource;

class ResidentFinanceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly FinanceService $financeService,
        private readonly FineRepositoryInterface $fineRepository,
        private readonly FineService $fineService,
        private readonly RefundRequestRepositoryInterface $refundRequestRepository,
    ) {}

    /**
     * Ringkasan Keuangan Penghuni
     *
     * Menampilkan ringkasan tagihan belum bayar (unpaid) dan informasi sewa aktif pengguna saat ini.
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Daftar Tagihan Saya
     *
     * Melihat semua tagihan yang dibebankan kepada penghuni yang sedang login.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
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

    /**
     * Detail Tagihan Saya
     *
     * Melihat detail satu tagihan spesifik milik penghuni yang sedang login.
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Riwayat Pembayaran Saya
     *
     * Melihat daftar transaksi/pembayaran yang pernah dilakukan oleh penghuni yang sedang login.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
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

    /**
     * Inisiasi Perpanjang Sewa (Manual)
     *
     * Mengajukan perpanjangan masa sewa dan generate tagihan untuk pembayaran manual (transfer bank).
     * @return JsonResponse
     */
    public function initiatePerpanjangManual(InitiatePerpanjangSewaRequest $request, int $scheduleId): JsonResponse
    {
        $userId = Auth::id();

        $activeTenant = DB::table('finance_active_tenants')
            ->where('user_id', $userId)
            ->where('schedule_id', $scheduleId)
            ->first();

        if (! $activeTenant) {
            return $this->apiError('Sewa aktif tidak ditemukan.', 404);
        }

        $schedule = DB::table('room_schedules')->where('id', $scheduleId)->first();

        if (! $schedule || ! $schedule->agreed_price || (float) $schedule->agreed_price <= 0) {
            return $this->apiError('Harga sewa belum diatur. Hubungi admin.', 422);
        }

        $statusTerminal = [
            PaymentStatus::FAILED->value,
            PaymentStatus::REJECTED->value,
            PaymentStatus::REFUNDED->value,
        ];

        $hasPendingExtension = DB::table('invoices')
            ->where('schedule_id', $scheduleId)
            ->where('status', InvoiceStatus::UNPAID->value)
            ->where('period_start', '>', $schedule->end_date)
            ->where(function ($q) {
                // Jika sudah melewati payment_expires_at, tidak dianggap pending
                $q->whereNull('payment_expires_at')
                  ->orWhere('payment_expires_at', '>', now());
            })
            ->whereNotExists(function ($query) use ($statusTerminal) {
                $query->from('payments')
                    ->whereColumn('payments.invoice_id', 'invoices.id')
                    ->whereIn('payments.status', $statusTerminal);
            })
            ->exists();

        if ($hasPendingExtension) {
            return $this->apiError('Masih ada tagihan perpanjangan yang belum dibayar. Selesaikan pembayaran terlebih dahulu sebelum memperpanjang kembali.', 422);
        }

        $durationMonths   = $request->integer('duration_months');
        $currentEndDate   = Carbon::parse($schedule->end_date);
        $newEndDate       = $currentEndDate->copy()->addMonths($durationMonths);
        $amount           = (float) $schedule->agreed_price * $durationMonths;
        $suffix           = strtoupper(substr(md5(uniqid()), 0, 6));
        $invoiceNumber    = 'EXT-' . date('Ymd') . '-' . str_pad($scheduleId, 4, '0', STR_PAD_LEFT) . '-' . $suffix;
        $paymentExpiresAt = now()->addMinutes(15);

        $invoice = DB::transaction(function () use (
            $scheduleId, $schedule, $activeTenant, $userId,
            $invoiceNumber, $amount, $currentEndDate, $newEndDate,
            $paymentExpiresAt, $statusTerminal
        ) {
            // Batalkan invoice perpanjangan lama yang expired atau semua pembayarannya gagal
            DB::table('invoices')
                ->where('schedule_id', $scheduleId)
                ->where('status', InvoiceStatus::UNPAID->value)
                ->where('period_start', '>', $schedule->end_date)
                ->update(['status' => InvoiceStatus::CANCELLED->value, 'updated_at' => now()]);

            return $this->invoiceRepository->create([
                'schedule_id'        => $scheduleId,
                'invoice_number'     => $invoiceNumber,
                'amount'             => $amount,
                'status'             => InvoiceStatus::UNPAID->value,
                'due_date'           => now()->toDateString(),
                'payment_expires_at' => $paymentExpiresAt,
                'tenant_user_id'     => $userId,
                'tenant_name'        => $activeTenant->tenant_name,
                'room_number'        => $activeTenant->room_number,
                'period_start'       => $currentEndDate->copy()->addDay()->toDateString(),
                'period_end'         => $newEndDate->toDateString(),
            ]);
        });

        return $this->apiSuccess(
            new InvoiceResource($invoice),
            'Invoice perpanjangan berhasil dibuat. Silakan upload bukti transfer dalam 15 menit.',
            201
        );
    }

    /**
     * Perpanjang Sewa (Otomatis/Midtrans)
     *
     * Mengajukan perpanjangan masa sewa dan langsung memproses pembayaran via payment gateway (Midtrans).
     * @return JsonResponse
     */
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

        // Blokir jika ada tagihan perpanjangan yang masih aktif:
        // - belum pernah dicoba bayar, ATAU ada pembayaran yang masih pending/diproses.
        // Invoice yang semua upaya bayarnya sudah terminal (failed/rejected/refunded) boleh diretry.
        $statusTerminal = [
            PaymentStatus::FAILED->value,
            PaymentStatus::REJECTED->value,
            PaymentStatus::REFUNDED->value,
        ];

        $hasPendingExtension = DB::table('invoices')
            ->where('schedule_id', $scheduleId)
            ->where('status', InvoiceStatus::UNPAID->value)
            ->where('period_start', '>', $schedule->end_date)
            ->whereNotExists(function ($query) use ($statusTerminal) {
                $query->from('payments')
                    ->whereColumn('payments.invoice_id', 'invoices.id')
                    ->whereIn('payments.status', $statusTerminal);
            })
            ->exists();

        if ($hasPendingExtension) {
            return $this->apiError('Masih ada tagihan perpanjangan yang belum dibayar. Selesaikan pembayaran terlebih dahulu sebelum memperpanjang kembali.', 422);
        }

        $durationMonths = $request->integer('duration_months');
        $currentEndDate = Carbon::parse($schedule->end_date);
        $newEndDate     = $currentEndDate->copy()->addMonths($durationMonths);
        $amount         = (float) $schedule->agreed_price * $durationMonths;
        $suffix         = strtoupper(substr(md5(uniqid()), 0, 6));
        $invoiceNumber  = 'EXT-' . date('Ymd') . '-' . str_pad($scheduleId, 4, '0', STR_PAD_LEFT) . '-' . $suffix;

        // Bungkus cleanup + pembuatan invoice + pemrosesan pembayaran dalam satu transaction.
        // Jika processPayment gagal (mis. Midtrans API error), invoice juga ikut di-rollback
        // sehingga tidak ada invoice yatim yang memblokir retry berikutnya.
        $payment = DB::transaction(function () use (
            $scheduleId, $schedule, $activeTenant, $userId,
            $request, $invoiceNumber, $amount, $currentEndDate, $newEndDate, $statusTerminal
        ) {
            // Batalkan invoice perpanjangan lama yang semua pembayarannya sudah gagal
            // agar tidak ada duplikat invoice aktif untuk periode yang sama.
            DB::table('invoices')
                ->where('schedule_id', $scheduleId)
                ->where('status', InvoiceStatus::UNPAID->value)
                ->where('period_start', '>', $schedule->end_date)
                ->update(['status' => InvoiceStatus::CANCELLED->value, 'updated_at' => now()]);

            // Buat invoice untuk periode perpanjangan.
            // end_date pada room_schedules TIDAK diubah di sini — akan diupdate oleh listener
            // setelah pembayaran berhasil dikonfirmasi.
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

            // processPayment punya nested transaction sendiri; jika ia throw,
            // exception merambat ke sini dan seluruh outer transaction di-rollback.
            return $this->financeService->processPayment($invoice->id, $request->validated());
        });

        return $this->apiSuccess(
            new PaymentResource($payment),
            'Perpanjangan sewa berhasil diproses',
            201
        );
    }

    public function myFines(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $status = $request->query('status');

        $fines = $this->fineRepository->getByUser(
            $userId,
            $status ? ['status' => $status] : []
        );

        return FineResource::collection($fines)
            ->additional(['success' => true, 'message' => 'Daftar denda berhasil dimuat.'])
            ->response();
    }

    public function bayarDenda(BayarDendaRequest $request): JsonResponse
    {
        $userId = Auth::id();
        $data   = $request->validated();

        $paymentData = [
            'payment_method'         => $data['payment_method'],
            'preferred_payment_type' => $data['preferred_payment_type'] ?? null,
        ];

        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $paymentData['payment_proof_bytes'] = $file->get();
            $paymentData['payment_proof_name']  = $file->getClientOriginalName();
        }

        $payment = $this->fineService->bayarDenda($userId, $data['fine_ids'], $paymentData);

        return $this->apiSuccess(
            new PaymentResource($payment),
            'Pembayaran denda berhasil diproses.',
            201
        );
    }

    public function ajukanPembatalanDp(Request $request, int $scheduleId): JsonResponse
    {
        $request->validate([
            'bank_name'           => 'required|string|max:100',
            'account_number'      => 'required|string|max:50',
            'account_holder_name' => 'required|string|max:150',
        ]);

        $refundRequest = $this->financeService->ajukanPembatalanDp(
            scheduleId: $scheduleId,
            userId: Auth::id(),
            bankData: $request->only(['bank_name', 'account_number', 'account_holder_name']),
        );

        return $this->apiSuccess(
            new RefundRequestResource($refundRequest),
            'Permintaan pembatalan DP berhasil diajukan. Menunggu persetujuan admin.',
            201
        );
    }

    public function myRefundRequests(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $scheduleIds = DB::table('room_schedules')
            ->where('tenant_user_id', $userId)
            ->pluck('id')
            ->toArray();

        if (empty($scheduleIds)) {
            return $this->apiSuccess(
                ['data' => [], 'meta' => ['total' => 0]],
                'Belum ada permintaan pembatalan.'
            );
        }

        $perPage = (int) $request->query('per_page', 15);
        $filters = array_merge(
            $request->only(['status']),
            ['schedule_ids' => $scheduleIds]
        );

        $refundRequests = $this->refundRequestRepository->getPaginated($perPage, $filters);

        return $this->apiSuccess(
            RefundRequestResource::collection($refundRequests)->response()->getData(true),
            'Daftar permintaan pembatalan Anda'
        );
    }
}
