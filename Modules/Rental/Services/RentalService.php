<?php

namespace Modules\Rental\Services;

use Carbon\Carbon;
use Modules\Rental\Enums\LeaseStatus;
use Modules\Rental\Enums\RentalType;
use Modules\Rental\Models\Lease;
use Modules\Rental\Repositories\Contracts\LeaseRepositoryInterface;
use Modules\Resident\Repositories\Contracts\ResidentRepositoryInterface;
use Modules\Room\Contracts\RoomAvailabilityService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RentalService
{
    public function __construct(
        private readonly LeaseRepositoryInterface $leaseRepository,
        private readonly ResidentRepositoryInterface $residentRepository,
        private readonly RoomAvailabilityService $roomAvailabilityService
    ) {}

    public function createLease(int $userId, array $data): Lease
    {
        $resident = $this->residentRepository->findByUserId($userId);

        if (!$resident) {
            throw new HttpException(403, 'Anda harus melengkapi biodata KTP terlebih dahulu sebelum menyewa kamar.');
        }

        if (!$this->roomAvailabilityService->isAvailable($data['room_id'])) {
            throw new HttpException(422, 'Mohon maaf, kamar ini tidak tersedia atau sedang disewa');
        }

        $startDate = Carbon::parse($data['start_date']);
        $endDate = $startDate->copy()->addMonths($data['duration']);

        $lease = $this->leaseRepository->create([
            'resident_id' => $resident->id,
            'room_id' => $data['room_id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'rental_type' => RentalType::MONTHLY->value,
            'status' => LeaseStatus::PENDING->value,
        ]);

        return $lease->load('room');
    }

    public function getMyLeases(int $userId)
    {
        $resident = $this->residentRepository->findByUserId($userId);

        if (!$resident) {
            throw new NotFoundHttpException('Biodata penghuni tidak ditemukan');
        }

        return $this->leaseRepository->getByResidentId($resident->id);
    }
}
