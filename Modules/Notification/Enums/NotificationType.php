<?php

namespace Modules\Notification\Enums;

enum NotificationType: string
{
    case PAYMENT_RECEIPT = 'payment_receipt';
    case PAYMENT_REMINDER = 'payment_reminder';
    case PEMBAYARAN_DITERIMA = 'pembayaran_diterima';
    case PEMBAYARAN_DIBATALKAN = 'pembayaran_dibatalkan';
    case JADWAL_DIBUAT = 'jadwal_dibuat';
    case JADWAL_SEWA_AKTIF = 'jadwal_sewa_aktif';
    case JADWAL_SEWA_SELESAI = 'jadwal_sewa_selesai';
    case JADWAL_BATAL = 'jadwal_batal';
    case LAPORAN_KERUSAKAN = 'laporan_kerusakan';
    case MANUAL_BROADCAST = 'manual_broadcast';
    case SYSTEM_ALERT = 'system_alert';
    case GUEST_REGISTERED = 'guest_registered';
    case GUEST_STAY_ENDED = 'guest_stay_ended';
}
