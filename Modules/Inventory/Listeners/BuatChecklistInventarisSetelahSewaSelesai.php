<?php

namespace Modules\Inventory\Listeners;

use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Support\Facades\Log;

class BuatChecklistInventarisSetelahSewaSelesai
{
    public function handle(JadwalSewaSelesai $event): void
    {
        // Placeholder: ketika Schedule Core (Fase 7) selesai dan inventaris
        // memiliki room_id, listener ini akan membuat checklist kondisi barang
        // untuk kamar yang baru saja dikosongkan.
        Log::info('Inventory checklist diperlukan', [
            'schedule_id' => $event->scheduleId,
            'room_id' => $event->roomId,
            'room_number' => $event->roomNumber,
        ]);
    }
}
