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
            ]);
            return;
        }

        $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($event->scheduleId, 4, '0', STR_PAD_LEFT);

        $this->invoiceRepository->create([
            'lease_id'       => $event->scheduleId,
            'invoice_number' => $invoiceNumber,
            'amount'         => $event->agreedPrice,
            'status'         => InvoiceStatus::UNPAID->value,
            'due_date'       => Carbon::parse($event->startDate),
        ]);

        Log::info('Invoice dibuat via event JadwalDibuat', [
            'schedule_id'    => $event->scheduleId,
            'invoice_number' => $invoiceNumber,
        ]);
    }
}
