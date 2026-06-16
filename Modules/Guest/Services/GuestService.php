<?php

namespace Modules\Guest\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Guest\Models\Guest;
use Modules\Guest\Repositories\Contracts\GuestRepositoryInterface;
use Modules\Guest\Services\GuestBillingService;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Services\NotificationService;
use Modules\Rental\Enums\LeaseStatus;
use Modules\Rental\Repositories\Contracts\LeaseRepositoryInterface;
use Modules\Resident\Repositories\Contracts\ResidentRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GuestService
{
    public function __construct(
        private readonly GuestRepositoryInterface $guestRepository,
        private readonly LeaseRepositoryInterface $leaseRepository,
        private readonly ResidentRepositoryInterface $residentRepository,
        private readonly GuestBillingService $billingService,
        private readonly NotificationService $notificationService,
    ) {}

    public function getMyGuests(int $userId): Collection
    {
        $lease = $this->resolveActiveLease($userId);

        return $this->guestRepository->getByLeaseId($lease->id);
    }

    public function addGuest(int $userId, array $data): Guest
    {
        $lease = $this->resolveActiveLease($userId);

        $lease->loadMissing('room');

        $billing = $this->billingService->calculateBilling($lease, $data['check_in_at'], $data['check_out_at']);

        $guest = $this->guestRepository->create([
            'lease_id'      => $lease->id,
            'name'          => $data['name'],
            'check_in_at'   => $data['check_in_at'],
            'check_out_at'  => $data['check_out_at'],
            'relationship'  => $data['relationship'],
            'total_days'    => $billing['total_days'],
            'billable_days' => $billing['billable_days'],
            'charge_amount' => $billing['charge_amount'],
        ]);

        $this->billingService->createBillIfNeeded($guest, $billing['billable_days'], (float) $billing['charge_amount']);

        $this->logGuestRegistered($guest);

        return $guest;
    }

    public function addGuestByLease(int $leaseId, array $data): Guest
    {
        try {
            $lease = $this->leaseRepository->findById($leaseId);
        } catch (ModelNotFoundException $e) {
            throw new NotFoundHttpException('Data sewa tidak ditemukan.');
        }

        if ($lease->status !== LeaseStatus::ACTIVE) {
            throw new HttpException(422, 'Sewa tidak aktif untuk menambahkan tamu.');
        }

        $lease->loadMissing('room');

        $billing = $this->billingService->calculateBilling($lease, $data['check_in_at'], $data['check_out_at']);

        $guest = $this->guestRepository->create([
            'lease_id'      => $lease->id,
            'name'          => $data['name'],
            'check_in_at'   => $data['check_in_at'],
            'check_out_at'  => $data['check_out_at'],
            'relationship'  => $data['relationship'],
            'total_days'    => $billing['total_days'],
            'billable_days' => $billing['billable_days'],
            'charge_amount' => $billing['charge_amount'],
        ]);

        $this->billingService->createBillIfNeeded($guest, $billing['billable_days'], (float) $billing['charge_amount']);

        $this->logGuestRegistered($guest);

        return $guest;
    }

    public function deleteGuest(int $userId, int $guestId): void
    {
        $lease = $this->resolveActiveLease($userId);

        $guest = $this->guestRepository->findById($guestId);

        if (!$guest || $guest->lease_id !== $lease->id) {
            throw new NotFoundHttpException('Data tamu tidak ditemukan atau bukan milik Anda.');
        }

        $this->guestRepository->delete($guest);
    }

    public function checkoutGuest(int $guestId): Guest
    {
        $guest = $this->guestRepository->findById($guestId);

        if (!$guest) {
            throw new NotFoundHttpException('Data tamu tidak ditemukan.');
        }

        if ($guest->stay_completed_notified_at) {
            throw new HttpException(422, 'Tamu sudah ditandai keluar sebelumnya.');
        }

        return $this->markGuestStayEnded($guest, now(), true);
    }

    public function checkoutMyGuest(int $userId, int $guestId): Guest
    {
        $lease = $this->resolveActiveLease($userId);

        $guest = $this->guestRepository->findById($guestId);

        if (!$guest || $guest->lease_id !== $lease->id) {
            throw new NotFoundHttpException('Data tamu tidak ditemukan atau bukan milik Anda.');
        }

        if ($guest->stay_completed_notified_at) {
            throw new HttpException(422, 'Tamu sudah ditandai keluar sebelumnya.');
        }

        return $this->markGuestStayEnded($guest, now(), true);
    }

    private function resolveActiveLease(int $userId): \Modules\Rental\Models\Lease
    {
        $resident = $this->residentRepository->findByUserId($userId);

        if (!$resident) {
            throw new HttpException(403, 'Anda belum melengkapi biodata penghuni.');
        }

        $lease = $this->leaseRepository->getByResidentId($resident->id)
            ->firstWhere('status', LeaseStatus::ACTIVE);

        if (!$lease) {
            throw new HttpException(403, 'Anda tidak memiliki sewa aktif untuk mendaftarkan tamu.');
        }

        return $lease;
    }

    private function logGuestRegistered(Guest $guest): void
    {
        $guest->loadMissing(['lease.resident.user', 'lease.room']);

        $lease      = $guest->lease;
        $roomNumber = $lease?->room?->number ?? '-';
        $checkIn    = $guest->check_in_at?->format('d/m/Y') ?? '-';

        $message = "Tamu pada kamar ({$roomNumber}) akan menginap mulai tanggal ({$checkIn}).";

        $this->notificationService->logSystemNotification(
            NotificationType::GUEST_REGISTERED,
            $message,
            'admin'
        );
    }

    public function markGuestStayEnded(Guest $guest, \Carbon\CarbonInterface $checkoutAt, bool $forceCheckoutTime = false): Guest
    {
        $guest->loadMissing(['lease.resident.user', 'lease.room']);

        if ($forceCheckoutTime || !$guest->check_out_at || $guest->check_out_at->gt($checkoutAt)) {
            $guest->check_out_at = $checkoutAt;
        }

        $guest->stay_completed_notified_at = now();
        $guest->save();

        $message = $this->formatStayEndedMessage($guest, $guest->check_out_at);

        $this->notificationService->logSystemNotification(
            NotificationType::GUEST_STAY_ENDED,
            $message,
            'admin'
        );

        return $guest;
    }

    private function formatStayEndedMessage(Guest $guest, ?\Carbon\CarbonInterface $checkoutAt): string
    {
        $lease = $guest->lease;
        $residentName = $lease?->resident?->user?->name ?? '-';
        $roomTitle = $lease?->room?->title ?? '-';
        $roomNumber = $lease?->room?->number ?? '-';
        $relationship = $guest->relationship?->label() ?? '-';
        $checkOut = $checkoutAt?->format('Y-m-d H:i') ?? '-';

        return "Masa menginap tamu telah selesai: {$guest->name} ({$relationship}) dari penghuni {$residentName}. "
            . "Kamar: {$roomTitle} No. {$roomNumber}. Check-out: {$checkOut}.";
    }
}
