<?php

namespace Modules\Guest\Enums;

enum GuestRelationship: string
{
    case PARENT = 'parent';    // Orang Tua
    case SIBLING = 'sibling';   // Saudara
    case FRIEND = 'friend';    // Teman
    case RELATIVE = 'relative';  // Kerabat
    case COLLEAGUE = 'colleague'; // Rekan Kerja
    case OTHER = 'other';     // Lainnya

    public function label(): string
    {
        return match ($this) {
            self::PARENT => 'Orang Tua',
            self::SIBLING => 'Saudara',
            self::FRIEND => 'Teman',
            self::RELATIVE => 'Kerabat',
            self::COLLEAGUE => 'Rekan Kerja',
            self::OTHER => 'Lainnya',
        };
    }
}
