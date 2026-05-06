<?php

namespace Modules\Rental\Console\Commands;

use Illuminate\Console\Command;
use Modules\Rental\Enums\LeaseStatus;
use Modules\Rental\Models\Lease;
use Modules\Room\Enums\RoomStatus;
use Modules\Room\Models\Room;

class ExpirePendingLeases extends Command
{
    protected $signature = 'rental:expire-pending-leases';

    protected $description = 'Batalkan lease pending yang melewati batas waktu pembayaran 5 menit';

    public function handle(): void
    {
        $expired = Lease::where('status', LeaseStatus::PENDING->value)
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($expired as $lease) {
            // Hanya batalkan jika belum ada pembayaran yang diunggah
            $hasPayment = $lease->latestInvoice?->payments()
                ->whereIn('status', ['pending', 'verified', 'paid'])
                ->exists();

            if ($hasPayment) {
                continue;
            }

            $lease->update([
                'status' => LeaseStatus::CANCELLED->value,
                'finished_at' => now(),
            ]);

            // Kembalikan status kamar menjadi tersedia
            Room::where('id', $lease->room_id)
                ->where('status', RoomStatus::OCCUPIED->value)
                ->update(['status' => RoomStatus::AVAILABLE->value]);

            $count++;
        }

        $this->info("Berhasil membatalkan {$count} lease yang kadaluarsa.");
    }
}
