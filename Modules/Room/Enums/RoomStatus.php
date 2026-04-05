<?php

namespace Modules\Room\Enums;

enum RoomStatus: string
{
    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case MAINTENANCE = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Tersedia',
            self::OCCUPIED => 'Terisi',
            self::MAINTENANCE => 'Perbaikan',
        };
    }
}
