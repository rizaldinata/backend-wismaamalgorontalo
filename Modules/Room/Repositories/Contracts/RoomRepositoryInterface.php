<?php

namespace Modules\Room\Repositories\Contracts;

use Modules\Room\Models\Room;
use Modules\Room\Models\RoomImage;

interface RoomRepositoryInterface
{
    public function getAllPaginated(array $filters = []);
    public function findById(int $id): Room;
    public function create(array $data): Room;
    public function update(Room $room, array $data): Room;
    public function delete(Room $room): void;
    public function getAllWithSchedules();

    // relasi ke gambar kamar
    public function addImage(Room $room, array $imageData): RoomImage;
    public function findImageById(int $imageId): RoomImage;
    public function deleteImage(RoomImage $image): void;
}
