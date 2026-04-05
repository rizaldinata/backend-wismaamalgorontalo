<?php

namespace Modules\Room\Contracts;

interface RoomAvailabilityService
{
    /**
     * Cek apakah kamar tersedia untuk disewa
     */
    public function isAvailable(int $roomId): bool;

    /**
     * Ubah status kamar menjadi terisi (Occupied)
     */
    public function markAsOccupied(int $roomId): void;

    /**
     * Ubah status kamar menjadi tersedia (Available)
     */
    public function markAsAvailable(int $roomId): void;

    /**
     * Ambil harga kamar (penting untuk perhitungan sewa)
     */
    public function getPrice(int $roomId): float;

    /**
     * Ambil nama kamar (untuk invoice/display)
     */
    public function getName(int $roomId): string;
}
