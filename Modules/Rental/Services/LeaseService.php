<?php

namespace Modules\Rental\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Rental\Enums\LeaseStatus;
use Modules\Rental\Models\Lease;
use Modules\Room\Contracts\RoomAvailabilityService;

class LeaseService
{
    protected $roomService;

    // Inject Room Service Contract
    public function __construct(RoomAvailabilityService $roomService)
    {
        $this->roomService = $roomService;
    }

    /**
     * User mengajukan sewa baru
     */
    public function createLeaseRequest($user, array $data, $paymentProof = null): Lease
    {
        // 1. Cek Ketersediaan Kamar (Inter-module communication)
        if (!$this->roomService->isAvailable($data['room_id'])) {
            throw new Exception("Kamar yang dipilih tidak tersedia.");
        }

        return DB::transaction(function () use ($user, $data, $paymentProof) {
            $startDate = Carbon::parse($data['start_date']);
            $duration = (int) $data['duration_months'];
            $endDate = $startDate->copy()->addMonths($duration);

            // Ambil harga real-time dari Module Room
            $pricePerMonth = $this->roomService->getPrice($data['room_id']);
            $totalPrice = $pricePerMonth * $duration;

            // Upload Bukti Bayar (jika ada)
            $proofPath = null;
            if ($paymentProof) {
                $proofPath = $paymentProof->store('payment_proofs', 'public');
            }

            $lease = Lease::create([
                'user_id' => $user->id,
                'room_id' => $data['room_id'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'price_per_month' => $pricePerMonth,
                'total_price' => $totalPrice,
                'status' => LeaseStatus::PENDING, // Menunggu konfirmasi admin
                'payment_proof' => $proofPath,
                'notes' => $data['notes'] ?? null,
            ]);

            return $lease;
        });
    }

    /**
     * Admin menyetujui sewa
     */
    public function approveLease(int $leaseId): Lease
    {
        return DB::transaction(function () use ($leaseId) {
            $lease = Lease::findOrFail($leaseId);

            if ($lease->status !== LeaseStatus::PENDING) {
                throw new Exception("Hanya pengajuan PENDING yang bisa disetujui.");
            }

            // 1. Update Status Lease
            $lease->update(['status' => LeaseStatus::ACTIVE]);

            // 2. Update Status Kamar jadi OCCUPIED (Panggil Module Room)
            $this->roomService->markAsOccupied($lease->room_id);

            return $lease;
        });
    }

    /**
     * Admin menolak/membatalkan sewa
     */
    public function rejectLease(int $leaseId, string $reason = null): Lease
    {
        return DB::transaction(function () use ($leaseId, $reason) {
            $lease = Lease::findOrFail($leaseId);

            // Jika sebelumnya active, kembalikan status kamar jadi available
            if ($lease->status === LeaseStatus::ACTIVE) {
                $this->roomService->markAsAvailable($lease->room_id);
            }

            $lease->update([
                'status' => LeaseStatus::CANCELLED,
                'notes' => $reason ? $lease->notes . " [REJECT REASON: $reason]" : $lease->notes
            ]);

            return $lease;
        });
    }

    public function getUserLeases($userId)
    {
        return Lease::where('user_id', $userId)
            ->with(['room.images']) // Eager load relasi room
            ->latest()
            ->get();
    }
}
