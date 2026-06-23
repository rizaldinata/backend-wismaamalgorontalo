<?php

namespace Modules\Schedule\Services;

use App\Events\Jadwal\DPDibayar;
use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalDibuat;
use App\Events\Jadwal\JadwalSewaAktif;
use App\Events\Jadwal\JadwalSewaSelesai;
use Carbon\Carbon;
use Modules\Schedule\Enums\SchedulePaymentScheme;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Enums\ScheduleType;
use Modules\Schedule\Models\Schedule;
use Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ScheduleService
{
    public function __construct(
        private readonly ScheduleRepositoryInterface $scheduleRepository,
    ) {}

    public function buatJadwal(array $data): Schedule
    {
        if ($this->scheduleRepository->hasPendingOrActiveByRoomId($data['room_id'])) {
            throw new \DomainException('Kamar ini sudah memiliki jadwal sewa yang sedang berlangsung atau menunggu konfirmasi.');
        }

        $paymentScheme = SchedulePaymentScheme::from($data['payment_scheme'] ?? 'full');

        if (($data['type'] ?? '') === 'sewa') {
            $startDate = Carbon::parse($data['start_date'])->startOfDay();

            if ($paymentScheme === SchedulePaymentScheme::DP && $startDate->lte(now()->addDays(7)->startOfDay())) {
                throw new \DomainException('Pembayaran DP hanya tersedia untuk pemesanan dengan tanggal masuk lebih dari 7 hari ke depan.');
            }

            if (! empty($data['tenant_user_id'])) {
                $profile = \Modules\Auth\Models\UserProfile::where('user_id', $data['tenant_user_id'])->first();
                if (! $profile || empty($profile->id_card_number) || empty($profile->phone_number) || empty($profile->address_ktp)) {
                    throw new \DomainException('Profil belum lengkap. Silakan lengkapi biodata Anda (NIK, nomor telepon, dan alamat KTP) sebelum memesan kamar.');
                }
                if (empty($data['tenant_phone'])) {
                    $data['tenant_phone'] = $profile->phone_number;
                }
            }
        }

        $dpAmount = null;
        if ($paymentScheme === SchedulePaymentScheme::DP && ! empty($data['agreed_price'])) {
            $dpAmount = round((float) $data['agreed_price'] * 0.5, 2);
        }

        $schedule = $this->scheduleRepository->create([
            'room_id'          => $data['room_id'],
            'type'             => $data['type'],
            'status'           => ScheduleStatus::PENDING->value,
            'payment_scheme'   => $paymentScheme->value,
            'dp_amount'        => $dpAmount,
            'start_date'       => $data['start_date'],
            'end_date'         => $data['end_date'],
            'created_by'       => $data['created_by'] ?? null,
            'tenant_name'      => $data['tenant_name'] ?? null,
            'tenant_id_number' => $data['tenant_id_number'] ?? null,
            'tenant_phone'     => $data['tenant_phone'] ?? null,
            'tenant_id_photo'  => $data['tenant_id_photo'] ?? null,
            'tenant_user_id'   => $data['tenant_user_id'] ?? null,
            'agreed_price'     => $data['agreed_price'] ?? null,
        ]);

        $roomNumber = $data['room_number'] ?? ($schedule->room->number ?? '');

        event(new JadwalDibuat(
            scheduleId: $schedule->id,
            roomId: $schedule->room_id,
            roomNumber: $roomNumber,
            tipeJadwal: $schedule->type->value,
            startDate: $schedule->start_date->toDateString(),
            endDate: $schedule->end_date->toDateString(),
            tenantName: $schedule->tenant_name ?? '',
            tenantPhone: $schedule->tenant_phone ?? '',
            agreedPrice: $schedule->agreed_price ? (float) $schedule->agreed_price : null,
            source: 'schedule',
            tenantUserId: $schedule->tenant_user_id,
            paymentScheme: $paymentScheme->value,
            dpAmount: $dpAmount,
        ));

        return $schedule;
    }

    public function aktifkanJadwal(int $scheduleId): Schedule
    {
        $schedule = $this->scheduleRepository->findById($scheduleId);

        if (! in_array($schedule->status, [ScheduleStatus::PENDING, ScheduleStatus::TERKONFIRMASI])) {
            throw new HttpException(422, 'Hanya jadwal dengan status menunggu atau terkonfirmasi yang bisa diaktifkan.');
        }

        return $this->doAktifkan($schedule);
    }

    public function konfirmasiJadwal(int $scheduleId): Schedule
    {
        $schedule = $this->scheduleRepository->findById($scheduleId);

        if (! in_array($schedule->status, [ScheduleStatus::PENDING, ScheduleStatus::DP_TERBAYAR])) {
            throw new \DomainException('Jadwal hanya bisa dikonfirmasi dari status pending atau dp_terbayar.');
        }

        return $this->scheduleRepository->updateStatus($schedule, ScheduleStatus::TERKONFIRMASI->value);
    }

    public function aktifkanJadwalDariDP(int $scheduleId): Schedule
    {
        $schedule = $this->scheduleRepository->findById($scheduleId);

        if ($schedule->status !== ScheduleStatus::DP_TERBAYAR) {
            throw new \DomainException('Jadwal hanya bisa diaktifkan dari status DP Terbayar setelah pelunasan dilunasi.');
        }

        return $this->doAktifkan($schedule);
    }

    private function doAktifkan(Schedule $schedule): Schedule
    {
        $updated = $this->scheduleRepository->updateStatus(
            $schedule,
            ScheduleStatus::ACTIVE->value,
            ['activated_at' => now()]
        );

        if ($updated->type === ScheduleType::SEWA) {
            event(new JadwalSewaAktif(
                scheduleId: $updated->id,
                roomId: $updated->room_id,
                roomNumber: $updated->room->number ?? '',
                tenantName: $updated->tenant_name ?? '',
                tenantPhone: $updated->tenant_phone ?? '',
                startDate: $updated->start_date->toDateString(),
                userId: $updated->tenant_user_id,
                endDate: $updated->end_date->toDateString(),
            ));
        }

        return $updated;
    }

    public function selesaikanJadwal(int $scheduleId): Schedule
    {
        $schedule = $this->scheduleRepository->findById($scheduleId);

        if ($schedule->status !== ScheduleStatus::ACTIVE) {
            throw new HttpException(422, 'Hanya jadwal aktif yang bisa diselesaikan.');
        }

        $updated = $this->scheduleRepository->updateStatus(
            $schedule,
            ScheduleStatus::FINISHED->value,
            ['finished_at' => now()]
        );

        if ($updated->type === ScheduleType::SEWA) {
            $masihAdaSewaAktif = $updated->tenant_user_id
                ? $this->scheduleRepository->getActiveByTenantUserId($updated->tenant_user_id) !== null
                : false;

            event(new JadwalSewaSelesai(
                scheduleId: $updated->id,
                roomId: $updated->room_id,
                roomNumber: $updated->room->number ?? '',
                tenantName: $updated->tenant_name ?? '',
                tenantPhone: $updated->tenant_phone ?? '',
                endDate: $updated->end_date->toDateString(),
                userId: $updated->tenant_user_id,
                masihAdaSewaAktif: $masihAdaSewaAktif,
            ));
        }

        return $updated;
    }

    public function batalkanJadwal(int $scheduleId): Schedule
    {
        $schedule = $this->scheduleRepository->findById($scheduleId);

        if (in_array($schedule->status, [ScheduleStatus::FINISHED, ScheduleStatus::CANCELLED])) {
            throw new HttpException(422, 'Jadwal sudah selesai atau sudah dibatalkan.');
        }

        $updated = $this->scheduleRepository->updateStatus(
            $schedule,
            ScheduleStatus::CANCELLED->value,
            ['finished_at' => now()]
        );

        $masihAdaSewaAktif = $updated->tenant_user_id && $updated->type === ScheduleType::SEWA
            ? $this->scheduleRepository->getActiveByTenantUserId($updated->tenant_user_id) !== null
            : false;

        event(new JadwalBatal(
            scheduleId: $updated->id,
            roomId: $updated->room_id,
            roomNumber: $updated->room->number ?? '',
            tipeJadwal: $updated->type->value,
            tenantName: $updated->tenant_name ?? '',
            tenantPhone: $updated->tenant_phone ?? '',
            userId: $updated->tenant_user_id,
            masihAdaSewaAktif: $masihAdaSewaAktif,
        ));

        return $updated;
    }

    public function ambilJadwalById(int $scheduleId): Schedule
    {
        return $this->scheduleRepository->findById($scheduleId);
    }

    public function ambilJadwalAktifKamar(int $roomId): ?Schedule
    {
        return $this->scheduleRepository->getActiveByRoomId($roomId);
    }

    public function ambilSemuaJadwal(array $filters = []): mixed
    {
        return $this->scheduleRepository->getAllPaginated($filters);
    }

    public function ambilJadwalKamar(int $roomId): iterable
    {
        return $this->scheduleRepository->getByRoomId($roomId);
    }
}
