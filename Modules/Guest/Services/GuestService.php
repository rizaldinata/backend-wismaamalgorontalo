<?php

namespace Modules\Guest\Services;

use App\Services\ImageService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Guest\Models\Guest;
use Modules\Guest\Models\GuestActiveContext;
use Modules\Guest\Repositories\Contracts\GuestRepositoryInterface;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Services\NotificationService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GuestService
{
    public function __construct(
        private readonly GuestRepositoryInterface $guestRepository,
        private readonly GuestBillingService $billingService,
        private readonly ImageService $imageService,
    ) {}

    private function logNotification(NotificationType $type, string $message): void
    {
        if (app()->bound(NotificationService::class)) {
            app(NotificationService::class)->logSystemNotification($type, $message);
        }
    }

    public static function getRecentGuestsByUserId(int $userId, int $limit = 5): array
    {
        return Guest::where('user_id', $userId)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($guest) {
                return [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'relationship' => $guest->relationship->value ?? $guest->relationship,
                    'check_in_at' => $guest->check_in_at,
                    'check_out_at' => $guest->check_out_at,
                ];
            })
            ->toArray();
    }

    public function getMyGuests(int $userId): Collection
    {
        $context = $this->resolveActiveContext($userId);

        if ($context->schedule_id) {
            return $this->guestRepository->getByScheduleId($context->schedule_id);
        }

        return $this->guestRepository->getByLeaseId($context->lease_id);
    }

    public function addGuest(int $userId, array $data): Collection
    {
        $context = $this->resolveActiveContext($userId);

        return $this->processMultipleGuests($context, $data);
    }

    public function addGuestByLease(int $leaseId, array $data): Collection
    {
        $context = GuestActiveContext::where('lease_id', $leaseId)
            ->where('is_active', true)
            ->first();

        if (! $context) {
            throw new HttpException(422, 'Sewa tidak aktif untuk menambahkan tamu.');
        }

        return $this->processMultipleGuests($context, $data);
    }

    public function addGuestBySchedule(int $scheduleId, array $data): Collection
    {
        $context = GuestActiveContext::where('schedule_id', $scheduleId)
            ->where('is_active', true)
            ->first();

        if (! $context) {
            throw new HttpException(422, 'Sewa tidak aktif untuk menambahkan tamu.');
        }

        return $this->processMultipleGuests($context, $data);
    }

    private function processMultipleGuests(GuestActiveContext $context, array $data): Collection
    {
        $billing = $this->billingService->calculateBilling(
            (float) $context->room_price,
            $data['check_in_at'],
            $data['check_out_at']
        );

        $createdGuests = new Collection;
        $guestNames = [];

        foreach ($data['guests'] as $guestData) {
            // Handle identity image upload if present
            $identityImagePath = null;
            if (isset($guestData['identity_image']) && $guestData['identity_image'] instanceof \Illuminate\Http\UploadedFile) {
                $identityImagePath = $this->imageService->uploadAndCompress($guestData['identity_image'], 'guest-identity');
            }

            $guest = $this->guestRepository->create([
                'lease_id' => $context->lease_id,
                'user_id' => $context->user_id,
                'schedule_reference_id' => $context->schedule_id,
                'tenant_name' => $context->tenant_name,
                'tenant_email' => $context->tenant_email,
                'tenant_phone' => $context->tenant_phone,
                'name' => $guestData['name'],
                'check_in_at' => $data['check_in_at'],
                'check_out_at' => $data['check_out_at'],
                'relationship' => $guestData['relationship'],
                'identity_image_path' => $identityImagePath,
                'total_days' => $billing['total_days'],
                'billable_days' => $billing['billable_days'],
                'charge_amount' => $billing['charge_amount'],
            ]);

            $this->billingService->createBillIfNeeded($guest, $billing['billable_days'], (float) $billing['charge_amount']);

            $createdGuests->push($guest);
            $guestNames[] = $guest->name;
        }

        $namesStr = implode(', ', $guestNames);
        $message = "Tamu terdaftar: {$namesStr} (Penghuni: {$context->tenant_name}).";
        $this->logNotification(NotificationType::GUEST_REGISTERED, $message);

        return $createdGuests;
    }

    public function deleteGuest(int $userId, int $guestId): void
    {
        $guest = $this->guestRepository->findById($guestId);

        // Verifikasi kepemilikan via user_id (data baru) atau via relasi lease (data lama, sebelum Fase 4)
        $ownerId = $guest->user_id ?? $guest->lease?->resident?->user_id;

        if (! $guest || $ownerId !== $userId) {
            throw new NotFoundHttpException('Data tamu tidak ditemukan atau bukan milik Anda.');
        }

        $this->guestRepository->delete($guest);
    }

    public function checkoutGuest(int $guestId): Guest
    {
        $guest = $this->guestRepository->findById($guestId);

        if (! $guest) {
            throw new NotFoundHttpException('Data tamu tidak ditemukan.');
        }

        if ($guest->stay_completed_notified_at) {
            throw new HttpException(422, 'Tamu sudah ditandai keluar sebelumnya.');
        }

        return $this->markGuestStayEnded($guest, now(), true);
    }

    public function checkoutMyGuest(int $userId, int $guestId): Guest
    {
        $guest = $this->guestRepository->findById($guestId);

        // Verifikasi kepemilikan via user_id (data baru) atau via relasi lease (data lama, sebelum Fase 4)
        $ownerId = $guest->user_id ?? $guest->lease?->resident?->user_id;

        if (! $guest || $ownerId !== $userId) {
            throw new NotFoundHttpException('Data tamu tidak ditemukan atau bukan milik Anda.');
        }

        if ($guest->stay_completed_notified_at) {
            throw new HttpException(422, 'Tamu sudah ditandai keluar sebelumnya.');
        }

        return $this->markGuestStayEnded($guest, now(), true);
    }

    private function resolveActiveContext(int $userId): GuestActiveContext
    {
        $context = GuestActiveContext::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (! $context) {
            throw new HttpException(403, 'Anda tidak memiliki sewa aktif untuk mendaftarkan tamu.');
        }

        return $context;
    }

    public function markGuestStayEnded(Guest $guest, $endDate, bool $isEarly = false): Guest
    {
        $this->guestRepository->update($guest, [
            'check_out_at' => $endDate,
            'stay_completed_notified_at' => now(),
        ]);

        $status = $isEarly ? 'keluar lebih awal' : 'selesai menginap';
        $message = "Tamu {$guest->name} telah {$status} pada {$endDate}.";
        $this->logNotification(NotificationType::GUEST_STAY_ENDED, $message);

        return $guest;
    }

    public function extendGuestStay(int $guestId, string $newCheckOutAt): Guest
    {
        $guest = $this->guestRepository->findById($guestId);
        if (! $guest) {
            throw new NotFoundHttpException('Tamu tidak ditemukan.');
        }

        $context = GuestActiveContext::where('schedule_id', $guest->schedule_reference_id)
            ->where('is_active', true)->first();

        if (! $context) {
            throw new HttpException(422, 'Sewa penghuni sudah tidak aktif, tidak dapat diperpanjang.');
        }

        $billing = $this->billingService->calculateBilling(
            (float) $context->room_price,
            $guest->check_in_at->toIso8601String(),
            $newCheckOutAt
        );

        $oldTotal = $guest->charge_amount;

        $this->guestRepository->update($guest, [
            'check_out_at' => $newCheckOutAt,
            'total_days' => $billing['total_days'],
            'billable_days' => $billing['billable_days'],
            'charge_amount' => $billing['charge_amount'],
        ]);

        $newTotal = $billing['charge_amount'];
        $diffAmount = $newTotal - $oldTotal;

        if ($newTotal > 0) {
            $bill = $guest->bill; // this gets the latest bill
            if ($bill) {
                if (in_array($bill->status, [\Modules\Guest\Enums\GuestBillStatus::PAID, \Modules\Guest\Enums\GuestBillStatus::VERIFIED])) {
                    if ($diffAmount > 0) {
                        // Create a new bill for the difference
                        $this->billingService->createBillIfNeeded($guest, 0, $diffAmount);
                    }
                } else {
                    // Update the existing unpaid/pending bill
                    $bill->update([
                        'amount' => $bill->amount + $diffAmount,
                        'admin_notes' => 'Diperbarui karena perpanjangan menginap. '.$bill->admin_notes,
                    ]);
                }
            } else {
                $this->billingService->createBillIfNeeded($guest, $billing['billable_days'], (float) $newTotal);
            }
        }

        $message = "Masa menginap tamu {$guest->name} (Penghuni: {$context->tenant_name}) telah diperpanjang hingga {$newCheckOutAt}.";
        $this->logNotification(NotificationType::GUEST_STAY_EXTENDED, $message);

        return $guest;
    }
}
