<?php

namespace Modules\Rental\Services;

use Carbon\Carbon;
use Modules\Finance\Services\FinanceService;
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
        $rentalType = RentalType::tryFrom($data['rental_type']) ?? RentalType::MONTHLY;

        $endDate = $rentalType === RentalType::DAILY
            ? $startDate->copy()->addDays((int) $data['duration'])
            : $startDate->copy()->addMonths((int) $data['duration']);

        $lease = $this->leaseRepository->create([
            'resident_id' => $resident->id,
            'room_id' => $data['room_id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'rental_type' => $rentalType->value,
            'status' => LeaseStatus::PENDING->value,
        ]);

        $pricePerUnit = $this->roomAvailabilityService->getPrice($data['room_id'], $rentalType);
        $totalAmount = $pricePerUnit * $data['duration'];

        $financeService = app(FinanceService::class);
        $financeService->generateInvoiceForLease($lease->id, $totalAmount, $startDate);

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

    public function activateLease(int $leaseId): void
    {
        $lease = $this->leaseRepository->findById($leaseId);

        $this->leaseRepository->updateStatus($lease, LeaseStatus::ACTIVE->value);

        $this->roomAvailabilityService->markAsOccupied($lease->room_id);
    }

    public function cancelLease(int $leaseId)
    {
        $lease = $this->leaseRepository->findById($leaseId);

        if ($lease) {
            $lease->update(['status' => 'cancelled']);
            $lease->room()->update(['status' => 'Tersedia']);
        }
    }
}
