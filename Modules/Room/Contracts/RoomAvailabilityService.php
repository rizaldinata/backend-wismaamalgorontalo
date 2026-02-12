<?php

namespace Modules\Room\Contracts;

interface RoomAvailabilityService
{
    /**
     * Cek apakah kamar tersedia
     */
    public function isAvailable(int $roomId): bool;

    /**
     * Ubah status kamar menjadi terisi
     */
    public function markAsOccupied(int $roomId): void;

    /**
     * Ubah status kamar menjadi tersedia
     */
    public function markAsAvailable(int $roomId): void;

    /**
     * Ambil harga kamar
     */
    public function getPrice(int $roomId): float;
}
