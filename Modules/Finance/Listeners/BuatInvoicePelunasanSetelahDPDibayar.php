<?php

namespace Modules\Finance\Listeners;

use App\Events\Jadwal\DPDibayar;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;

class BuatInvoicePelunasanSetelahDPDibayar
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function handle(DPDibayar $event): void
    {
        $invoiceNumber = 'PLN-'.date('Ymd').'-'.str_pad($event->scheduleId, 4, '0', STR_PAD_LEFT);

        $this->invoiceRepository->create([
            'schedule_id'    => $event->scheduleId,
            'type'           => 'pelunasan',
            'invoice_number' => $invoiceNumber,
            'amount'         => $event->pelunasanAmount,
            'status'         => InvoiceStatus::UNPAID->value,
            'due_date'       => Carbon::parse($event->startDate),
            'tenant_user_id' => $event->tenantUserId,
            'tenant_name'    => $event->tenantName,
            'tenant_phone'   => $event->tenantPhone,
            'room_number'    => $event->roomNumberSnapshot,
            'period_start'   => $event->periodStart,
            'period_end'     => $event->periodEnd,
        ]);

        Log::info('Invoice pelunasan dibuat setelah DP terbayar.', [
            'schedule_id'     => $event->scheduleId,
            'invoice_number'  => $invoiceNumber,
            'pelunasan_amount' => $event->pelunasanAmount,
        ]);
    }
}
