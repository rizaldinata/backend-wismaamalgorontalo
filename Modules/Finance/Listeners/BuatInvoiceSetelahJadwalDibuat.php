<?php

namespace Modules\Finance\Listeners;

use App\Events\Jadwal\JadwalDibuat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;

class BuatInvoiceSetelahJadwalDibuat
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function handle(JadwalDibuat $event): void
    {
        if ($event->tipeJadwal !== 'sewa') {
            return;
        }

        if ($event->agreedPrice === null || $event->agreedPrice <= 0) {
            Log::warning('BuatInvoiceSetelahJadwalDibuat: agreedPrice tidak tersedia, invoice tidak dibuat.', [
                'schedule_id' => $event->scheduleId,
                'source' => $event->source,
            ]);

            return;
        }

        $invoiceNumber = 'INV-'.date('Ymd').'-'.str_pad($event->scheduleId, 4, '0', STR_PAD_LEFT);

        // Jalur baru (ScheduleService): simpan ke schedule_id; lease_id dibiarkan null
        // Jalur lama (RentalService):   simpan ke lease_id; schedule_id dibiarkan null
        $invoiceData = [
            'invoice_number' => $invoiceNumber,
            'amount' => $event->agreedPrice,
            'status' => InvoiceStatus::UNPAID->value,
            'due_date' => Carbon::parse($event->startDate),
            'tenant_user_id' => $event->tenantUserId,
            'tenant_name' => $event->tenantName,
            'tenant_phone' => $event->tenantPhone,
            'room_number' => $event->roomNumber,
            'period_start' => $event->startDate,
            'period_end' => $event->endDate,
        ];

        if ($event->source === 'schedule') {
            $invoiceData['schedule_id'] = $event->scheduleId;
        } else {
            $invoiceData['lease_id'] = $event->scheduleId;
        }

        $this->invoiceRepository->create($invoiceData);

        Log::info('Invoice dibuat via event JadwalDibuat', [
            'schedule_id' => $event->scheduleId,
            'source' => $event->source,
            'invoice_number' => $invoiceNumber,
        ]);
    }
}
