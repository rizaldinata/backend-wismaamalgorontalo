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

    /**
     * Daftar Kamar
     *
     * Mengambil daftar seluruh kamar berserta status ketersediaannya.
     * @unauthenticated
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $rooms = $this->roomService->getAllRooms($request->query());

        return $this->apiSuccess(
            RoomResource::collection($rooms)->response()->getData(true),
            'Data kamar berhasil diambil'
        );
    }

    /**
     * Jadwal Kamar (Public)
     *
     * Mengambil daftar kamar beserta jadwal kosong terdekat untuk ditampilkan di katalog aplikasi.
     * @unauthenticated
     * @return \Illuminate\Http\JsonResponse
     */
    public function schedules()
    {
        $rooms = $this->roomService->getRoomSchedules();

        return $this->apiSuccess(
            RoomResource::collection($rooms),
            'Jadwal kamar berhasil diambil'
        );
    }

    /**
     * Tambah Kamar Baru
     *
     * Membuat data kamar baru beserta foto dan fasilitasnya. (Hanya Admin)
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreRoomRequest $request)
    {
        $room = $this->roomService->createRoom(
            $request->validated(),
            $request->file('images', [])
        );

        return $this->apiSuccess(new RoomResource($room), 'Kamar berhasil dibuat', 201);
    }

    /**
     * Detail Kamar
     *
     * Mengambil detail lengkap suatu kamar berdasarkan ID.
     * @unauthenticated
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $room = $this->roomService->getRoomDetails($id);

        return $this->apiSuccess(new RoomResource($room), 'Detail kamar berhasil diambil');
    }

    /**
     * Update Kamar
     *
     * Memperbarui data kamar berdasarkan ID. (Hanya Admin)
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRoomRequest $request, $id)
    {
        $updatedRoom = $this->roomService->updateRoom(
            $id,
            $request->validated(),
            $request->file('images', [])
        );

        return $this->apiSuccess(new RoomResource($updatedRoom), 'Kamar berhasil diupdate');
    }

    /**
     * Hapus Kamar
     *
     * Menghapus data kamar berdasarkan ID secara permanen. (Hanya Admin)
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $this->roomService->deleteRoom($id);

        return $this->apiSuccess(null, 'Kamar berhasil dihapus');
    }

    /**
     * Upload Foto Kamar
     *
     * Menambahkan foto-foto baru ke kamar yang sudah ada. (Hanya Admin)
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImages(Request $request, $id)
    {
        $request->validate(['images' => 'required|array', 'images.*' => 'image|max:5120']);
        $room = $this->roomService->getRoomDetails($id);

        $this->roomService->uploadImages($room, $request->file('images'));

        return $this->apiSuccess(new RoomResource($room->refresh()), 'Foto berhasil ditambahkan');
    }

    /**
     * Hapus Foto Kamar
     *
     * Menghapus foto kamar tertentu. (Hanya Admin)
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage($roomId, $imageId)
    {
        $this->roomService->deleteImage($imageId);

        return $this->apiSuccess(null, 'Foto berhasil dihapus');
    }
}
