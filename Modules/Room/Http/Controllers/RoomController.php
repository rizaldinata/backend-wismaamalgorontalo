<?php

namespace Modules\Room\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Room\Http\Requests\StoreRoomRequest;
use Modules\Room\Http\Requests\UpdateRoomRequest;
use Modules\Room\Services\RoomService;
use Modules\Room\Models\Room;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class RoomController extends Controller
{
    use ApiResponse;

    protected $roomService;

    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    public function index(Request $request)
    {
        $rooms = $this->roomService->getAllRooms($request->query());
        return $this->apiSuccess($rooms, 'Data kamar berhasil diambil');
    }

    public function store(StoreRoomRequest $request)
    {
        try {
            $data = $request->validated();
            $images = $request->file('images', []);

            $room = $this->roomService->createRoom($data, $images);

            return $this->apiSuccess($room, 'Kamar berhasil dibuat', 201);
        } catch (\Exception $e) {
            return $this->apiError($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $room = Room::with('images')->findOrFail($id);
        return $this->apiSuccess($room, 'Detail kamar berhasil diambil');
    }

    public function update(UpdateRoomRequest $request, $id)
    {
        try {
            $room = Room::findOrFail($id);
            $data = $request->validated();
            $newImages = $request->file('images', []);

            $updatedRoom = $this->roomService->updateRoom($room, $data, $newImages);

            return $this->apiSuccess($updatedRoom, 'Kamar berhasil diupdate');
        } catch (\Exception $e) {
            return $this->apiError($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $room = Room::findOrFail($id);
            $this->roomService->deleteRoom($room);
            return $this->apiSuccess(null, 'Kamar berhasil dihapus');
        } catch (\Exception $e) {
            return $this->apiError($e->getMessage(), 500);
        }
    }

    public function deleteImage($imageId)
    {
        try {
            $this->roomService->deleteImage($imageId);
            return $this->apiSuccess(null, 'Foto berhasil dihapus');
        } catch (\Exception $e) {
            return $this->apiError('Gagal menghapus foto', 500);
        }
    }
}
