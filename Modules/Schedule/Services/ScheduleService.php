<?php

namespace Modules\Schedule\Services;

use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalDibuat;
use App\Events\Jadwal\JadwalSewaAktif;
use App\Events\Jadwal\JadwalSewaSelesai;
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
        $schedule = $this->scheduleRepository->create([
            'room_id'          => $data['room_id'],
            'type'             => $data['type'],
            'status'           => ScheduleStatus::PENDING->value,
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

        event(new JadwalDibuat(
            scheduleId:  $schedule->id,
            roomId:      $schedule->room_id,
            roomNumber:  $data['room_number'] ?? '',
            tipeJadwal:  $schedule->type->value,
            startDate:   $schedule->start_date->toDateString(),
            endDate:     $schedule->end_date->toDateString(),
            tenantName:  $schedule->tenant_name ?? '',
            tenantPhone: $schedule->tenant_phone ?? '',
            agreedPrice: $schedule->agreed_price ? (float) $schedule->agreed_price : null,
        ));

        return $schedule;
    }

    public function aktifkanJadwal(int $scheduleId): Schedule
    {
        $schedule = $this->scheduleRepository->findById($scheduleId);

        if ($schedule->status !== ScheduleStatus::PENDING) {
            throw new HttpException(422, 'Hanya jadwal dengan status menunggu yang bisa diaktifkan.');
        }

        $updated = $this->scheduleRepository->updateStatus(
            $schedule,
            ScheduleStatus::ACTIVE->value,
            ['activated_at' => now()]
        );

        if ($updated->type === ScheduleType::SEWA) {
            event(new JadwalSewaAktif(
                scheduleId:  $updated->id,
                roomId:      $updated->room_id,
                roomNumber:  '',
                tenantName:  $updated->tenant_name ?? '',
                tenantPhone: $updated->tenant_phone ?? '',
                startDate:   $updated->start_date->toDateString(),
                userId:      $updated->tenant_user_id,
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
            event(new JadwalSewaSelesai(
                scheduleId:  $updated->id,
                roomId:      $updated->room_id,
                roomNumber:  '',
                tenantName:  $updated->tenant_name ?? '',
                tenantPhone: $updated->tenant_phone ?? '',
                endDate:     $updated->end_date->toDateString(),
                userId:      $updated->tenant_user_id,
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

        event(new JadwalBatal(
            scheduleId:  $updated->id,
            roomId:      $updated->room_id,
            roomNumber:  '',
            tipeJadwal:  $updated->type->value,
            tenantName:  $updated->tenant_name ?? '',
            tenantPhone: $updated->tenant_phone ?? '',
        ));

        return $updated;
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
