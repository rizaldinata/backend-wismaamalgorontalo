<?php

namespace Modules\Guest\Enums;

enum GuestBillStatus: string
{
    case UNPAID = 'unpaid';
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case PAID = 'paid';
    case REJECTED = 'rejected';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'Belum Dibayar',
            self::PENDING => 'Menunggu Verifikasi',
            self::VERIFIED => 'Terverifikasi',
            self::PAID => 'Lunas',
            self::REJECTED => 'Ditolak',
            self::FAILED => 'Gagal',
        };
    }
}
