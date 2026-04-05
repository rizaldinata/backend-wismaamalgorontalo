<?php

namespace Modules\Rental\Enums;

enum LeaseStatus: string
{
    case PENDING = 'pending';   // Menunggu pembayaran pertama
    case ACTIVE = 'active';     // Sedang menempati kamar
    case FINISHED = 'finished'; // Masa sewa habis / sudah keluar
    case CANCELLED = 'cancelled'; // Dibatalkan sebelum masuk
}
