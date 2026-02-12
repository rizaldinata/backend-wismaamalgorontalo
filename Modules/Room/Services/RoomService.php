<?php

namespace Modules\Room\Services;

use Modules\Room\Models\Room;
use Modules\Room\Models\RoomImage;
use Modules\Room\Enums\RoomStatus;
use Modules\Room\Contracts\RoomAvailabilityService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class RoomService implements RoomAvailabilityService
{
    public function getAllRooms(array $filters = [])
    {
        return Room::query()
            ->when(isset($filters['search']), function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            })
            ->when(isset($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->with(['images', 'activeLease'])
            ->paginate(10);
    }

    public function createRoom(array $data, array $images = []): Room
    {
        return DB::transaction(function () use ($data, $images) {
            // 1. Create Room
            $room = Room::create($data);

            // 2. Handle Images if exists
            if (!empty($images)) {
                $this->uploadImages($room, $images);
            }

            return $room;
        });
    }

    public function updateRoom(Room $room, array $data, array $newImages = []): Room
    {
        return DB::transaction(function () use ($room, $data, $newImages) {
            $room->update($data);

            if (!empty($newImages)) {
                $this->uploadImages($room, $newImages);
            }

            return $room;
        });
    }

    public function deleteRoom(Room $room): void
    {
        DB::transaction(function () use ($room) {
            foreach ($room->images as $image) {
                Storage::disk('public')->delete([$image->image_path, $image->thumbnail_path]);
            }
            $room->images()->delete();
            $room->delete();
        });
    }

    private function uploadImages(Room $room, array $files): void
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

            $room->images()->create([
                'image_path' => $path,
                'thumbnail_path' => $thumbPath,
                'order' => $index + 1
            ]);
        }
    }

    public function deleteImage(int $imageId): void
    {
        $image = RoomImage::findOrFail($imageId);
        Storage::disk('public')->delete([$image->image_path, $image->thumbnail_path]);
        $image->delete();
    }

    public function isAvailable(int $roomId): bool
    {
        $room = Room::find($roomId);
        return $room && $room->status === RoomStatus::AVAILABLE;
    }

    public function markAsOccupied(int $roomId): void
    {
        $room = Room::findOrFail($roomId);
        $room->update(['status' => RoomStatus::OCCUPIED]);
    }

    public function markAsAvailable(int $roomId): void
    {
        $room = Room::findOrFail($roomId);
        $room->update(['status' => RoomStatus::AVAILABLE]);
    }

    public function getPrice(int $roomId): float
    {
        return Room::findOrFail($roomId)->price;
    }

    public function getName(int $roomId): string
    {
        return Room::findOrFail($roomId)->name;
    }
}
