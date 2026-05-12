<?php

namespace Modules\Notification\Enums;

enum NotificationType: string
{
    case PAYMENT_RECEIPT = 'payment_receipt';
    case PAYMENT_REMINDER = 'payment_reminder';
    case PEMBAYARAN_DITERIMA = 'pembayaran_diterima';
    case JADWAL_DIBUAT = 'jadwal_dibuat';
    case JADWAL_SEWA_AKTIF = 'jadwal_sewa_aktif';
    case JADWAL_SEWA_SELESAI = 'jadwal_sewa_selesai';
    case JADWAL_BATAL = 'jadwal_batal';
    case MANUAL_BROADCAST = 'manual_broadcast';
    case SYSTEM_ALERT = 'system_alert';
}