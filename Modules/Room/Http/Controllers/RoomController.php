<?php

namespace Modules\Room\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Room\Models\Room;
use App\Http\Controllers\Controller;
use Modules\Room\Http\Requests\StoreRoomRequest;
use Modules\Room\Transformers\RoomResource;
use App\Traits\ApiResponse;
use Modules\Room\Http\Requests\UpdateRoomRequest;

class RoomController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $room = Room::latest()->get();
        return $this->apiSuccess(RoomResource::collection($room), 'List data kamar');
    }

    public function store(StoreRoomRequest $request)
    {
        $room = Room::create($request->validated());

        return $this->apiSuccess($room, 'Kamar berhasil ditambahkan', 201);
    }

    public function show($id)
    {
        $room = Room::find($id);
        if (!$room) return $this->apiError('Kamar tidak ditemukan', 404);

        return $this->apiSuccess($room, 'Detail data kamar');
    }

    public function update(UpdateRoomRequest $request, $id)
    {
        $room = Room::find($id);

        if (!$room) {
            return $this->apiError('Kamar tidak ditemukan', 404);
        }

        $room->update($request->validated());

        return $this->apiSuccess($room, 'Data kamar berhasil diperbarui');
    }

    public function destroy($id)
    {
        $room = Room::find($id);

        if (!$room) {
            return $this->apiError('Kamar tidak ditemukan', 404);
        }

        $room->delete();

        return response()->json([
            'status' => true,
            'message' => 'Kamar berhasil dihapus',
        ], 200);
    }
}
