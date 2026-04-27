<?php

namespace Modules\Resident\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Rental\Enums\LeaseStatus;

class AdminResidentResource extends JsonResource
{
    public function toArray($request)
    {
        $latestInvoiceStatus = $this->latestInvoice?->status?->value ?? $this->latestInvoice?->status;
        $leaseStatus = $this->status?->value ?? $this->status;

        // Belum lunas jika belum ada invoice atau invoice terakhir bukan paid
        $isBelumLunas = $latestInvoiceStatus !== InvoiceStatus::PAID->value;

        // Pending jika status kontrak pending
        $isPending = $leaseStatus === LeaseStatus::PENDING->value;

        return [
            'id' => (string) $this->id,
            'nama' => $this->resident?->user?->name ?? '-',
            'kamar' => $this->room->number ?? '-',
            // Mengambil nomor HP dari relasi profile Resident
            'kontak' => $this->resident?->phone_number ?? '-',
            
            // Format yang diminta Flutter
            'detail_bayar' => $isBelumLunas ? 'Belum Lunas' : 'Lunas',
            'is_belum_lunas' => $isBelumLunas,
            'status' => $isPending ? 'Pending' : 'Aktif',
            'is_pending' => $isPending,
        ];
    }
}