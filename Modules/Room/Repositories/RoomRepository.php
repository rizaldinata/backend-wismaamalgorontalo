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
                $q->where('title', 'like', '%'.$filters['search'].'%')
                    ->orWhere('description', 'like', '%'.$filters['search'].'$')
                    ->orWhere('number', 'like', '%'.$filters['search'].'%');
            })
            ->when(isset($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when(isset($filters['is_highlighted']) && filter_var($filters['is_highlighted'], FILTER_VALIDATE_BOOLEAN), function ($q) {
                $raw = app(\Modules\Setting\Services\SettingService::class)->getSettingValue('landing_highlighted_rooms', '[]');
                $highlightedIds = json_decode(is_string($raw) ? $raw : '[]', true);
                if (is_array($highlightedIds) && count($highlightedIds) > 0) {
                    $q->whereIn('id', $highlightedIds);
                } else {
                    $q->limit(3); // Fallback to 3 newest rooms if no highlighted rooms are configured
                }
            })
            ->with(['images', 'activeSchedule'])
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
            'schedules' => function ($query) {
                $query->whereIn('status', ['pending', 'active', 'finished'])
                    ->orderByDesc('start_date');
            },
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
