<?php

namespace Modules\Room\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Modules\Room\Contracts\RoomAvailabilityService;
use Modules\Room\Enums\RoomStatus;
use Modules\Room\Models\Room;
use Modules\Room\Models\RoomImage;
use Modules\Room\Repositories\Contracts\RoomRepositoryInterface;
use Modules\Rental\Enums\RentalType;

class RoomService implements RoomAvailabilityService
{
    public function __construct(
        private readonly RoomRepositoryInterface $roomRepository
    ) {}

    public function getAllRooms(array $filters = [])
    {
        return $this->roomRepository->getAllPaginated($filters);
    }

    public function getRoomDetails(int $id): Room
    {
        return $this->roomRepository->findById($id);
    }

    public function createRoom(array $data, array $images = []): Room
    {
        return DB::transaction(function () use ($data, $images) {
            $room = $this->roomRepository->create($data);

            if (!empty($images)) {
                $this->uploadImages($room, $images);
            }

            return $room->load('images');
        });
    }

    public function updateRoom(int $id, array $data, array $newImages = []): Room
    {
        return DB::transaction(function () use ($id, $data, $newImages) {
            $room = $this->roomRepository->findById($id);
            $room = $this->roomRepository->update($room, $data);

            if (!empty($newImages)) {
                $this->uploadImages($room, $newImages);
            }

            return $room->load('images');
        });
    }

    public function deleteRoom(int $id): void
    {
        DB::transaction(function () use ($id) {
            $room = $this->roomRepository->findById($id);
            $this->roomRepository->delete($room);
        });
    }

    public function uploadImages(Room $room, array $files): void
    {
        $manager = new ImageManager(new Driver());

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) continue;

            $extension = $file->getClientOriginalExtension();
            $filename = uniqid('room_') . '.' . $extension;

            $folder = "rooms/{$room->id}";
            $path = "$folder/$filename";
            $thumbPath = "$folder/thumb_$filename";

            if (!Storage::disk('public')->exists($folder)) {
                Storage::disk('public')->makeDirectory($folder);
            }

            $image = $manager->read($file);
            $image->scaleDown(width: 1200);
            Storage::disk('public')->put($path, (string) $image->encode());

            $thumb = $manager->read($file);
            $thumb->cover(300, 300);
            Storage::disk('public')->put($thumbPath, (string) $thumb->encode());

            $this->roomRepository->addImage($room, [
                'image_path' => $path,
                'thumbnail_path' => $thumbPath,
                'order' => $index + 1
            ]);
        }
    }

    public function deleteImage(int $imageId): void
    {
        $image = $this->roomRepository->findImageById($imageId);
        $this->roomRepository->deleteImage($image);
    }

    public function isAvailable(int $roomId): bool
    {
        $room = $this->roomRepository->findByid($roomId);
        return $room && $room->status === RoomStatus::AVAILABLE;
    }

    public function markAsOccupied(int $roomId): void
    {
        $room  = $this->roomRepository->findById($roomId);
        $this->roomRepository->update($room, ['status' => RoomStatus::OCCUPIED]);
    }

    public function markAsAvailable(int $roomId): void
    {
        $room = $this->roomRepository->findById($roomId);
        $this->roomRepository->update($room, ['status' => RoomStatus::AVAILABLE]);
    }

    public function getPrice(int $roomId, RentalType $type = RentalType::MONTHLY): float
    {
        $room = $this->roomRepository->findById($roomId);
        return $type === RentalType::DAILY ? (float) $room->price_daily : (float) $room->price;
    }

    public function getName(int $roomId): string
    {
        return $this->roomRepository->findById($roomId)->title ?? $this->roomRepository->findById($roomId)->number;
    }
}
