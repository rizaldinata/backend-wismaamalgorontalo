<?php

namespace Modules\Room\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Room\Models\Room;
use App\Http\Controllers\Controller;
use Modules\Room\Http\Requests\StoreRoomRequest;
use Modules\Room\Transformers\RoomResource;

class RoomController extends Controller
{
    use \App\Traits\ApiResponse;

    public function index()
    {
        $rooms = Room::latest()->get();

        return $this->apiSucces(RoomResource::collection($rooms), "List data kamar");
    }

    public function store(StoreRoomRequest $request)
    {
        $room = Room::create($request->validated());

        return $this->apiSucces($room, 'Kamar berhasil ditambahkan', 201);
    }

    public function show($id)
    {
        $room = Room::find($id);
        if (!$room) return $this->apiError('Kamar tidak ditemukan', 404);

        return $this->apiSucces($room);
    }

    public function update(Request $request, $id)
    {
        $room = Room::find($id);

        if (!$room) {
            return response()->json([
                'status' => false,
                'message' => 'Kamar tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'number' => 'required|unique:rooms,number,' . $id,
            'type' => 'required|string',
            'price' => 'required|numeric',
            'status' => 'required|in:available,occupied,maintenance',
            'description' => 'nullable|string',
        ]);

        $room->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Data kamar berhasil diperbarui',
            'data' => $room
        ], 200);
    }

    public function destroy($id)
    {
        $room = Room::find($id);

        if (!$room) {
            return response()->json(['status' => false, 'message' => 'Kamar tidak ditemukan'], 404);
        }

        $room->delete();

        return response()->json([
            'status' => true,
            'message' => 'Kamar berhasil dihapus',
        ], 200);
    }
}
