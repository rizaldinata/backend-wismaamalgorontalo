<?php

namespace Modules\Guest\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Guest\Models\Guest;
use Modules\Guest\Repositories\Contracts\GuestRepositoryInterface;
use Modules\Guest\Services\GuestBillingService;
use Modules\Rental\Enums\LeaseStatus;
use Modules\Rental\Repositories\Contracts\LeaseRepositoryInterface;
use Modules\Resident\Repositories\Contracts\ResidentRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GuestService
{
    public function __construct(
        private readonly GuestRepositoryInterface $guestRepository,
        private readonly LeaseRepositoryInterface $leaseRepository,
        private readonly ResidentRepositoryInterface $residentRepository,
        private readonly GuestBillingService $billingService,
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
}
