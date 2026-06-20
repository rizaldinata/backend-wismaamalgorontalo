<?php

namespace Modules\Finance\Enums;

enum JenisPengeluaranTetap: string
{
    case LISTRIK = 'listrik';
    case AIR     = 'air';
    case WIFI    = 'wifi';

    public function label(): string
    {
        return match ($this) {
            self::LISTRIK => 'Listrik',
            self::AIR     => 'Air',
            self::WIFI    => 'WiFi',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
