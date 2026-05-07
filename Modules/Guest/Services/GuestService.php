<?php

namespace Modules\Guest\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Guest\Models\Guest;
use Modules\Guest\Repositories\Contracts\GuestRepositoryInterface;
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
    ) {}

    public function getMyGuests(int $userId): Collection
    {
        $lease = $this->resolveActiveLease($userId);

        return $this->guestRepository->getByLeaseId($lease->id);
    }

    public function addGuest(int $userId, array $data): Guest
    {
        $lease = $this->resolveActiveLease($userId);

        return $this->guestRepository->create([
            'lease_id'     => $lease->id,
            'name'         => $data['name'],
            'check_in_at'  => $data['check_in_at'],
            'check_out_at' => $data['check_out_at'],
            'relationship' => $data['relationship'],
        ]);
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
