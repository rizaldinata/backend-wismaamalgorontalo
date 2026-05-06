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
            'payment_expires_at' => now()->addMinutes(5),
        ]);

        // Kunci kamar segera agar tidak bisa dipesan pengguna lain (status reserved = menunggu verifikasi)
        $this->roomAvailabilityService->markAsReserved($data['room_id']);

        $pricePerUnit = $this->roomAvailabilityService->getPrice($data['room_id'], $rentalType);
        $totalAmount = $pricePerUnit * $data['duration'];

        $financeService = app(FinanceService::class);
        $financeService->generateInvoiceForLease($lease->id, $totalAmount, $startDate);

        return $lease->load(['room', 'latestInvoice']);
    }

    public function getMyLeases(int $userId)
    {
        $resident = $this->residentRepository->findByUserId($userId);

        if (!$resident) {
            throw new NotFoundHttpException('Biodata penghuni tidak ditemukan');
        }

        return $this->leaseRepository->getByResidentId($resident->id);
    }

    public function getAllLeases(array $filters = [])
    {
        return $this->leaseRepository->getAllPaginated($filters);
    }

    public function updateLeaseStatus(int $leaseId, string $status): Lease
    {
        $lease = $this->leaseRepository->findById($leaseId);
        
        // Logika bisnis tambahan berdasarkan status
        if ($status === LeaseStatus::ACTIVE->value && $lease->status->value !== LeaseStatus::ACTIVE->value) {
            $this->roomAvailabilityService->markAsOccupied($lease->room_id);
        }

        if (in_array($status, [LeaseStatus::CANCELLED->value, LeaseStatus::FINISHED->value]) 
            && in_array($lease->status->value, [LeaseStatus::ACTIVE->value, LeaseStatus::PENDING->value])) {
            $this->roomAvailabilityService->markAsAvailable($lease->room_id);
        }

        return $this->leaseRepository->updateStatus($lease, $status);
    }

    public function activateLease(int $leaseId): void
    {
        $lease = $this->leaseRepository->findById($leaseId);

        $this->leaseRepository->updateStatus($lease, LeaseStatus::ACTIVE->value);

        $this->roomAvailabilityService->markAsOccupied($lease->room_id);
    }

    public function extendLease(int $userId, int $leaseId, int $durationMonths): Lease
    {
        $resident = $this->residentRepository->findByUserId($userId);

        if (!$resident) {
            throw new HttpException(403, 'Anda belum melengkapi biodata penghuni.');
        }

        $oldLease = $this->leaseRepository->findById($leaseId);

        if (!$oldLease || $oldLease->resident_id !== $resident->id) {
            throw new NotFoundHttpException('Data sewa tidak ditemukan atau bukan milik Anda.');
        }

        if ($oldLease->status->value !== LeaseStatus::ACTIVE->value) {
            throw new HttpException(422, 'Hanya sewa yang sedang aktif yang bisa diperpanjang.');
        }

        if ($oldLease->rental_type->value !== RentalType::MONTHLY->value) {
            throw new HttpException(422, 'Saat ini hanya sewa bulanan yang bisa diperpanjang masa sewanya.');
        }

        // Cek apakah sudah ada perpanjangan (baik pending maupun active)
        $existingExtension = Lease::where('resident_id', $resident->id)
            ->where('room_id', $oldLease->room_id)
            ->where('start_date', '>=', $oldLease->end_date)
            ->whereIn('status', [LeaseStatus::PENDING->value, LeaseStatus::ACTIVE->value])
            ->exists();

        if ($existingExtension) {
            throw new HttpException(422, 'Gagal. Anda sudah memiliki perpanjangan aktif atau tagihan yang belum dibayar untuk periode setelah sewa ini. Silakan pilih sewa Anda yang paling terakhir jika ingin memperpanjang lagi.');
        }

        $newStartDate = $oldLease->end_date->copy();
        $newEndDate = $newStartDate->copy()->addMonths($durationMonths);

        $newLease = $this->leaseRepository->create([
            'resident_id' => $resident->id,
            'room_id' => $oldLease->room_id,
            'start_date' => $newStartDate,
            'end_date' => $newEndDate,
            'rental_type' => RentalType::MONTHLY->value,
            'status' => LeaseStatus::PENDING->value,
        ]);

        $pricePerUnit = $this->roomAvailabilityService->getPrice($oldLease->room_id, RentalType::MONTHLY);
        $totalAmount = $pricePerUnit * $durationMonths;

        $financeService = app(FinanceService::class);
        $financeService->generateInvoiceForLease($newLease->id, $totalAmount, $newStartDate);

        return $newLease->load('room');
    }

    public function cancelMyLease(int $userId, int $leaseId): void
    {
        $resident = $this->residentRepository->findByUserId($userId);

        if (!$resident) {
            throw new NotFoundHttpException('Data penghuni tidak ditemukan.');
        }

        $lease = $this->leaseRepository->findById($leaseId);

        if (!$lease || $lease->resident_id !== $resident->id) {
            throw new NotFoundHttpException('Data sewa tidak ditemukan atau bukan milik Anda.');
        }

        if ($lease->status->value !== LeaseStatus::PENDING->value) {
            throw new HttpException(422, 'Hanya sewa dengan status menunggu pembayaran yang bisa dibatalkan.');
        }

        $lease->update([
            'status' => LeaseStatus::CANCELLED->value,
            'finished_at' => now(),
        ]);

        $this->roomAvailabilityService->markAsAvailable($lease->room_id);
    }

    public function cancelLease(int $leaseId)
    {
        $lease = $this->leaseRepository->findById($leaseId);

        if ($lease) {
            $originalStatus = $lease->status->value;
            $comparisonDate = $lease->end_date ? $lease->end_date->copy() : now();

            if ($originalStatus === LeaseStatus::ACTIVE->value) {
                $lease->update([
                    'status' => LeaseStatus::FINISHED->value,
                    'finished_at' => now()
                ]);
            } else {
                $lease->update([
                    'status' => LeaseStatus::CANCELLED->value,
                    'finished_at' => now()
                ]);
            }

            // Buka kamar jika ini adalah sewa yang sedang bersinggungan hari ini
            if ($originalStatus === LeaseStatus::ACTIVE->value || $lease->start_date <= now()) {
                $this->roomAvailabilityService->markAsAvailable($lease->room_id);
            }

            // Cascade cancel future pending/active extensions for this resident and room
            Lease::where('resident_id', $lease->resident_id)
                ->where('room_id', $lease->room_id)
                ->where('start_date', '>=', $comparisonDate)
                ->where('id', '!=', $lease->id)
                ->whereNotIn('status', [LeaseStatus::CANCELLED->value, LeaseStatus::FINISHED->value])
                ->update(['status' => LeaseStatus::CANCELLED->value]);
        }
    }
}
