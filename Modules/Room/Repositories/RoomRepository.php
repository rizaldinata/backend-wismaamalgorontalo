<?php

namespace Modules\Room\Repositories;

use Modules\Room\Models\Room;
use Modules\Room\Models\RoomImage;
use Modules\Room\Repositories\Contracts\RoomRepositoryInterface;

class RoomRepository implements RoomRepositoryInterface
{
    public function getAllPaginated(array $filters = [])
    {
        return Room::query()
            ->when(isset($filters['search']), function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '$')
                    ->orWhere('number', 'like', '%' . $filters['search'] . '%');
            })
            ->when(isset($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->with(['images', 'activeLease'])
            ->latest()
            ->get();
    }

    public function findById(int $id): Room
    {
        return Room::with('images')->findOrFail($id);
    }

    public function create(array $data): Room
    {
        return Room::create($data);
    }

    public function update(Room $room, array $data): Room
    {
        $room->update($data);
        return $room;
    }

    public function getAllWithSchedules()
    {
        return Room::with([
            'leases' => function ($query) {
                $query->whereIn('status', [
                    'pending',
                    'active',
                    'finished',
                ]);
            },

            'leases.resident.user',
        ])->get();
    }

    public function delete(Room $room): void
    {
        $room->images()->delete();
        $room->delete();
    }

    public function addImage(Room $room, array $imageData): RoomImage
    {
        return $room->images()->create($imageData);
    }

    public function findImageById(int $imageId): RoomImage
    {
        return RoomImage::findOrFail($imageId);
    }

    public function deleteImage(RoomImage $image): void
    {
        $image->delete();
    }
}
