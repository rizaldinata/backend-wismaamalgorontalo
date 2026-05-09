<?php

namespace Modules\Room\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Room\Http\Requests\StoreRoomRequest;
use Modules\Room\Http\Requests\UpdateRoomRequest;
use Modules\Room\Services\RoomService;
use Modules\Room\Transformers\RoomResource;

class RoomController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly RoomService $roomService
    ) {}

    public function index(Request $request)
    {
        $rooms = $this->roomService->getAllRooms($request->query());
        return $this->apiSuccess(
            RoomResource::collection($rooms)->response()->getData(true),
            'Data kamar berhasil diambil'
        );
    }

    public function schedules()
    {
        $rooms = $this->roomService->getRoomSchedules();
        return $this->apiSuccess(
            RoomResource::collection($rooms),
            'Jadwal kamar berhasil diambil'
        );
    }

    public function store(StoreRoomRequest $request)
    {
        $room = $this->roomService->createRoom(
            $request->validated(),
            $request->file('images', [])
        );

        return $this->apiSuccess(new RoomResource($room), 'Kamar berhasil dibuat', 201);
    }

    public function show($id)
    {
        $room = $this->roomService->getRoomDetails($id);
        return $this->apiSuccess(new RoomResource($room), 'Detail kamar berhasil diambil');
    }

    public function update(UpdateRoomRequest $request, $id)
    {
        $updatedRoom = $this->roomService->updateRoom(
            $id,
            $request->validated(),
            $request->file('images', [])
        );

        return $this->apiSuccess(new RoomResource($updatedRoom), 'Kamar berhasil diupdate');
    }

    public function destroy($id)
    {
        $this->roomService->deleteRoom($id);
        return $this->apiSuccess(null, 'Kamar berhasil dihapus');
    }

    public function uploadImages(Request $request, $id)
    {
        $request->validate(['images' => 'required|array', 'images.*' => 'image|max:5120']);
        $room = $this->roomService->getRoomDetails($id);

        $this->roomService->uploadImages($room, $request->file('images'));

        return $this->apiSuccess(new RoomResource($room->refresh()), 'Foto berhasil ditambahkan');
    }

    public function deleteImage($roomId, $imageId)
    {
        $this->roomService->deleteImage($imageId);
        return $this->apiSuccess(null, 'Foto berhasil dihapus');
    }
}
