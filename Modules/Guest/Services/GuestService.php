<?php

namespace Modules\Guest\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Guest\Models\Guest;
use Modules\Guest\Models\GuestActiveContext;
use Modules\Guest\Repositories\Contracts\GuestRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GuestService
{
    public function __construct(
        private readonly GuestRepositoryInterface $guestRepository,
        private readonly GuestBillingService $billingService,
    ) {}

    public function getMyGuests(int $userId): Collection
    {
        $context = $this->resolveActiveContext($userId);

        return $this->guestRepository->getByLeaseId($context->lease_id);
    }

    public function addGuest(int $userId, array $data): Guest
    {
        $context = $this->resolveActiveContext($userId);

        $billing = $this->billingService->calculateBilling(
            (float) $context->room_price,
            $data['check_in_at'],
            $data['check_out_at']
        );

        $guest = $this->guestRepository->create([
            'lease_id' => $context->lease_id,
            'user_id' => $userId,
            'schedule_reference_id' => $context->schedule_id,
            'tenant_name' => $context->tenant_name,
            'tenant_email' => $context->tenant_email,
            'tenant_phone' => $context->tenant_phone,
            'name' => $data['name'],
            'check_in_at' => $data['check_in_at'],
            'check_out_at' => $data['check_out_at'],
            'relationship' => $data['relationship'],
            'total_days' => $billing['total_days'],
            'billable_days' => $billing['billable_days'],
            'charge_amount' => $billing['charge_amount'],
        ]);

        $this->billingService->createBillIfNeeded($guest, $billing['billable_days'], (float) $billing['charge_amount']);

        return $guest;
    }

    public function addGuestByLease(int $leaseId, array $data): Guest
    {
        $context = GuestActiveContext::where('lease_id', $leaseId)
            ->where('is_active', true)
            ->first();

        if (! $context) {
            throw new HttpException(422, 'Sewa tidak aktif untuk menambahkan tamu.');
        }

        $billing = $this->billingService->calculateBilling(
            (float) $context->room_price,
            $data['check_in_at'],
            $data['check_out_at']
        );

        $guest = $this->guestRepository->create([
            'lease_id' => $leaseId,
            'user_id' => $context->user_id,
            'schedule_reference_id' => $context->schedule_id,
            'tenant_name' => $context->tenant_name,
            'tenant_email' => $context->tenant_email,
            'tenant_phone' => $context->tenant_phone,
            'name' => $data['name'],
            'check_in_at' => $data['check_in_at'],
            'check_out_at' => $data['check_out_at'],
            'relationship' => $data['relationship'],
            'total_days' => $billing['total_days'],
            'billable_days' => $billing['billable_days'],
            'charge_amount' => $billing['charge_amount'],
        ]);

        $this->billingService->createBillIfNeeded($guest, $billing['billable_days'], (float) $billing['charge_amount']);

        return $guest;
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
}
