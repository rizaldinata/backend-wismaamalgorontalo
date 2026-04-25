<?php

namespace Modules\Resident\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminResidentResource extends JsonResource
{
    public function toArray($request)
    {
        // Mengecek apakah status invoice terakhir sudah 'paid'
        $isBelumLunas = $this->latestInvoice ? $this->latestInvoice->status !== 'paid' : true;
        
        // Mengecek apakah status kontrak sewa masih pending
        $isPending = $this->status === 'pending';

        return [
            'id' => (string) $this->id,
            'nama' => $this->user->name ?? '-',
            'kamar' => $this->room->number ?? '-',
            // Mengambil nomor HP dari relasi profile Resident
            'kontak' => $this->user->resident->phone_number ?? '-',
            
            // Format yang diminta Flutter
            'detail_bayar' => $isBelumLunas ? 'Belum Lunas' : 'Lunas',
            'is_belum_lunas' => $isBelumLunas,
            'status' => $isPending ? 'Pending' : 'Aktif',
            'is_pending' => $isPending,
        ];
    }
}