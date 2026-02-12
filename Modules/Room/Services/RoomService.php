<?php

namespace Modules\Room\Services;

use Modules\Room\Models\Room;
use Modules\Room\Enums\RoomStatus;
use Modules\Room\Contracts\RoomAvailabilityService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class RoomService implements RoomAvailabilityService
{
    public function createRoom(array $data)
    {
        return Room::create($data);
    }

    public function uploadImages(Room $room, array $files)
    {
        $manager = new ImageManager(new Driver());
        $uploadedImages = [];

        foreach ($files as $index => $file) {
            $extension = $file->extension() ?: 'jpg';
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $path = 'rooms/' . $filename;
            $thumbPath = 'rooms/thumbs/' . $filename;

            if (!Storage::disk('public')->exists('rooms/thumbs')) {
                Storage::disk('public')->makeDirectory('rooms/thumbs');
            }

            // Optimize Image
            $image = $manager->read($file);
            $image->scaleDown(width: 1200);
            Storage::disk('public')->put($path, (string) $image->encodeByExtension($extension, quality: 80));

            // Thumbnail
            $thumb = $manager->read($file);
            $thumb->cover(400, 300);
            Storage::disk('public')->put($thumbPath, (string) $thumb->encodeByExtension($extension, quality: 70));

            $uploadedImages[] = $room->images()->create([
                'image_path' => $path,
                'thumbnail_path' => $thumbPath,
                'order' => $room->images()->count() + $index,
            ]);
        }

        return $uploadedImages;
    }

    // --- Implementasi Contract untuk Module Rental ---

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
}
