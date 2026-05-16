<?php

namespace Modules\Guest\Services;

use App\Services\ImageService;
use Exception;
use Illuminate\Http\UploadedFile;
use Midtrans\Config;
use Midtrans\Snap;
use Modules\Guest\Enums\GuestBillStatus;
use Modules\Guest\Models\Guest;
use Modules\Guest\Models\GuestBill;
use Modules\Guest\Repositories\Contracts\GuestBillRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GuestBillingService
{
    public function __construct(
        private readonly GuestBillRepositoryInterface $billRepository,
        private readonly ImageService $imageService,
    ) {}

    /**
     * Calculate billing fields for a guest stay.
     *
     * @return array{total_days: int, billable_days: int, charge_amount: float}
     */
    public function calculateBilling(float $roomPrice, string $checkIn, string $checkOut): array
    {
        $checkInDt = new \DateTime($checkIn);
        $checkOutDt = new \DateTime($checkOut);

        $diffHours = ($checkOutDt->getTimestamp() - $checkInDt->getTimestamp()) / 3600;
        $totalDays = (int) ceil($diffHours / 24);

        $billableDays = max(0, $totalDays - 2);
        $chargeAmount = $billableDays * ($roomPrice * 0.05);

        return [
            'total_days' => $totalDays,
            'billable_days' => $billableDays,
            'charge_amount' => $chargeAmount,
        ];
    }

    /**
     * Create a GuestBill record if chargeAmount > 0.
     */
    public function createBillIfNeeded(Guest $guest, int $billableDays, float $chargeAmount): ?GuestBill
    {
        if ($chargeAmount <= 0) {
            return null;
        }

        $billNumber = 'GB-'.date('Ymd').'-'.str_pad($guest->id, 5, '0', STR_PAD_LEFT);

        return $this->billRepository->create([
            'guest_id' => $guest->id,
            'bill_number' => $billNumber,
            'amount' => $chargeAmount,
            'status' => GuestBillStatus::UNPAID->value,
        ]);
    }

    /**
     * Resident pays via manual proof upload.
     */
    public function payManual(int $guestId, int $userId, UploadedFile $proof): GuestBill
    {
        $guest = $this->resolveOwnedGuest($guestId, $userId);

        $bill = $this->billRepository->findByGuestId($guestId);

        if (! $bill) {
            throw new NotFoundHttpException('Tagihan tidak ditemukan untuk tamu ini.');
        }

        if (! in_array($bill->status, [GuestBillStatus::UNPAID, GuestBillStatus::REJECTED])) {
            throw new HttpException(422, 'Tagihan tidak dapat dibayar karena statusnya bukan "Belum Dibayar" atau "Ditolak".');
        }

        $proofPath = $this->imageService->uploadAndCompress($proof, 'guest-bills/proof');

        return $this->billRepository->update($bill, [
            'payment_method' => 'manual',
            'payment_proof_path' => $proofPath,
            'status' => GuestBillStatus::PENDING->value,
        ]);
    }

    /**
     * Resident pays via Midtrans — generates a Snap token.
     */
    public function payMidtrans(int $guestId, int $userId): GuestBill
    {
        $guest = $this->resolveOwnedGuest($guestId, $userId);

        $bill = $this->billRepository->findByGuestId($guestId);

        if (! $bill) {
            throw new NotFoundHttpException('Tagihan tidak ditemukan untuk tamu ini.');
        }

        if (! in_array($bill->status, [GuestBillStatus::UNPAID, GuestBillStatus::REJECTED, GuestBillStatus::FAILED])) {
            throw new HttpException(422, 'Tagihan tidak dapat dibayar karena statusnya bukan "Belum Dibayar", "Ditolak", atau "Gagal".');
        }

        $transactionId = 'GB-TRX-'.time().'-'.$bill->id;

        // Configure Midtrans
        Config::$serverKey = config('finance.midtrans.server_key');
        Config::$isProduction = config('finance.midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
        Config::$overrideNotifUrl = url('/api/guests/bills/midtrans/notification');

        // Ambil data customer dari kolom snapshot di tabel guests
        $bill->loadMissing('guest');
        $guest = $bill->guest;

        // Fallback ke relasi Rental untuk records lama (sebelum Fase 4) yang belum punya tenant_*
        $tenantName = $guest->tenant_name ?? $guest->lease?->resident?->user?->name ?? 'Unknown';
        $tenantEmail = $guest->tenant_email ?? $guest->lease?->resident?->user?->email ?? '';
        $tenantPhone = $guest->tenant_phone ?? $guest->lease?->resident?->phone_number ?? '';

        $params = [
            'transaction_details' => [
                'order_id' => $transactionId,
                'gross_amount' => (int) $bill->amount,
            ],
            'customer_details' => [
                'first_name' => $tenantName,
                'email' => $tenantEmail,
                'phone' => $tenantPhone,
            ],
            'item_details' => [
                [
                    'id' => $bill->id,
                    'price' => (int) $bill->amount,
                    'quantity' => 1,
                    'name' => 'Tagihan Tamu #'.$bill->bill_number,
                ],
            ],
        ];

        $enabledPayments = config('finance.midtrans.enabled_payments', []);
        if (! empty($enabledPayments)) {
            $params['enabled_payments'] = $enabledPayments;
        }

        try {
            $snapToken = Snap::getSnapToken($params);

            return $this->billRepository->update($bill, [
                'payment_method' => 'midtrans',
                'transaction_id' => $transactionId,
                'snap_token' => $snapToken,
                'status' => GuestBillStatus::PENDING->value,
            ]);
        } catch (Exception $e) {
            $this->billRepository->update($bill, [
                'payment_method' => 'midtrans',
                'transaction_id' => $transactionId,
                'status' => GuestBillStatus::FAILED->value,
                'admin_notes' => 'Midtrans Error: '.$e->getMessage(),
            ]);

            throw new \DomainException('Gagal memproses metode pembayaran. Silakan coba kembali beberapa saat lagi.');
        }
    }

    /**
     * Admin verifies a pending manual bill.
     */
    public function verifyBill(int $billId, bool $isApproved, ?string $adminNotes = null): GuestBill
    {
        $bill = $this->billRepository->findById($billId);

        if (! $bill) {
            throw new NotFoundHttpException('Tagihan tidak ditemukan.');
        }

        if ($bill->status !== GuestBillStatus::PENDING) {
            throw new HttpException(422, 'Hanya tagihan berstatus "Menunggu Verifikasi" yang dapat diverifikasi.');
        }

        $updateData = [
            'status' => $isApproved ? GuestBillStatus::VERIFIED->value : GuestBillStatus::REJECTED->value,
            'admin_notes' => $adminNotes,
        ];

        if ($isApproved) {
            $updateData['paid_at'] = now();
        }

        return $this->billRepository->update($bill, $updateData);
    }

    /**
     * Handle Midtrans webhook notification.
     */
    public function handleMidtransNotification(array $payload): void
    {
        $transactionId = $payload['order_id'] ?? null;

        if (! $transactionId) {
            return;
        }

        $bill = $this->billRepository->findByTransactionId($transactionId);

        if (! $bill) {
            return;
        }

        $transactionStatus = $payload['transaction_status'] ?? '';

        if (in_array($transactionStatus, ['capture', 'settlement'])) {
            $this->billRepository->update($bill, [
                'status' => GuestBillStatus::PAID->value,
                'paid_at' => now(),
            ]);
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            $this->billRepository->update($bill, [
                'status' => GuestBillStatus::FAILED->value,
            ]);
        }
    }

    /**
     * Resolve a guest by ID and verify it belongs to the authenticated user's active lease.
     */
    private function resolveOwnedGuest(int $guestId, int $userId): Guest
    {
        $guest = Guest::find($guestId);

        if (! $guest) {
            throw new NotFoundHttpException('Data tamu tidak ditemukan.');
        }

        // Verifikasi kepemilikan via user_id (data baru) atau via relasi lease (data lama, sebelum Fase 4)
        $ownerId = $guest->user_id ?? $guest->lease?->resident?->user_id;

        if ($ownerId !== $userId) {
            throw new HttpException(403, 'Anda tidak memiliki akses ke data tamu ini.');
        }

        return $guest;
    }
}
