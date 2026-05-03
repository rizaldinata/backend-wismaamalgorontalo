<?php

namespace Modules\Resident\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminResidentResource extends JsonResource
{
    public function toArray($request)
    {
        $leaseStatus = $this->status?->value ?? $this->status;
        $hasPaymentProof = !empty($this->payment_proof ?? $this->payment_proof_path ?? null);
        $isBelumLunas = !$hasPaymentProof;
        $isPending = $leaseStatus === 'pending';

        return [
            'id' => (string) $this->id,
            'nama' => $this->resident?->user?->name ?? '-',
            'kamar' => $this->room->number ?? '-',
            'kontak' => $this->resident?->phone_number ?? '-',
            'detail_bayar' => $isBelumLunas ? 'Belum Lunas' : 'Lunas',
            'is_belum_lunas' => $isBelumLunas,
            'status' => $leaseStatus ?? '-',
            'is_pending' => $isPending,
        ];
    }
}