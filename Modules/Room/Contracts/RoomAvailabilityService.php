<?php

namespace Modules\Room\Contracts;

interface RoomAvailabilityService
{
    /**
     * Cek apakah kamar tersedia untuk disewa
     */
    public function isAvailable(int $roomId): bool;

    /**
     * Ubah status kamar menjadi dipesan/reserved (menunggu verifikasi pembayaran)
     */
    public function markAsReserved(int $roomId): void;

    /**
     * Ubah status kamar menjadi terisi (Occupied) — hanya setelah pembayaran diverifikasi
     */
    public function markAsOccupied(int $roomId): void;

    /**
     * Ubah status kamar menjadi tersedia (Available)
     */
    public function markAsAvailable(int $roomId): void;

    /**
     * Ambil harga kamar (penting untuk perhitungan sewa)
     */
    public function getPrice(int $roomId, string $type = 'monthly'): float;

    /**
     * Ambil nama kamar (untuk invoice/display)
     */
    public function getName(int $roomId): string;
}
