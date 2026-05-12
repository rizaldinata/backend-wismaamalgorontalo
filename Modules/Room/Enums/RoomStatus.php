<?php

namespace Modules\Room\Enums;

enum RoomStatus: string
{
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case OCCUPIED = 'occupied';
    case MAINTENANCE = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Tersedia',
            self::RESERVED => 'Dipesan',
            self::OCCUPIED => 'Terisi',
            self::MAINTENANCE => 'Perbaikan',
        };
    }
}
