<?php

namespace Modules\Room\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Room\Models\Room;
use App\Http\Controllers\Controller;
use Modules\Room\Http\Requests\StoreRoomRequest;
use Modules\Room\Transformers\RoomResource;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Modules\Room\Http\Requests\UpdateRoomRequest;

class RoomController extends Controller
{
    use ApiResponse;

    // menampilkan data semua kamar
    public function index()
    {
        $room = Room::with('images')->latest()->get();
        return $this->apiSuccess(RoomResource::collection($room), 'List data kamar');
    }


    // menambahkan data kamar baru
    public function store(StoreRoomRequest $request)
    {
        $room = Room::create($request->validated());

        return $this->apiSuccess($room, 'Kamar berhasil ditambahkan', 201);
    }

    // menampilkan data detail satu kamar
    public function show($id)
    {
        $room = Room::with('images')->find($id);
        if (!$room)
            return $this->apiError('Kamar tidak ditemukan', 404);

        return $this->apiSuccess(new RoomResource($room), 'Detail data kamar');
    }

    // memperbarui data kamar
    public function update(UpdateRoomRequest $request, $id)
    {
        $room = Room::find($id);

        if (!$room) {
            return $this->apiError('Kamar tidak ditemukan', 404);
        }

        $room->update($request->validated());

        return $this->apiSuccess($room, 'Data kamar berhasil diperbarui');
    }

    // menghapus data kamar
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

    // upload foto kamar (multiple)
    public function uploadImages(Request $request, $id)
    {
        $room = Room::find($id);

        if (!$room) {
            return $this->apiError('Kamar tidak ditemukan', 404);
        }

        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // Tingkatkan ke 5MB karena akan diresize
        ]);

        $uploadedImages = [];
        $manager = new ImageManager(new Driver());

        foreach ($request->file('images') as $index => $file) {
            // 1. Generate Nama File Unik
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = 'rooms/' . $filename;
            $thumbPath = 'rooms/thumbs/' . $filename;

            // Pastikan direktori ada
            if (!Storage::disk('public')->exists('rooms/thumbs')) {
                Storage::disk('public')->makeDirectory('rooms/thumbs');
            }

            // 2. Proses Gambar Utama (Optimize & Resize ke max 1200px)
            $image = $manager->read($file);
            $image->scaleDown(width: 1200); // Resize jika lebih lebar dari 1200px (tetap jaga rasio)

            // Simpan gambar utama ke storage
            Storage::disk('public')->put($path, (string) $image->encodeByExtension(quality: 80));

            // 3. Proses Thumbnail (Resize ke max 400px)
            $thumb = $manager->read($file);
            $thumb->cover(400, 300); // Crop & Resize pas 400x300

            // Simpan thumbnail ke storage
            Storage::disk('public')->put($thumbPath, (string) $thumb->encodeByExtension(quality: 70));

            // 4. Simpan ke database
            $roomImage = $room->images()->create([
                'image_path' => $path,
                'thumbnail_path' => $thumbPath,
                'order' => $room->images()->count() + $index,
            ]);

            $uploadedImages[] = [
                'id' => $roomImage->id,
                'url' => $roomImage->image_url,
                'thumbnail' => $roomImage->thumbnail_url,
                'order' => $roomImage->order,
            ];
        }

        return $this->apiSuccess($uploadedImages, 'Foto berhasil dioptimasi dan diupload', 201);
    }

    // hapus foto kamar
    public function deleteImage($roomId, $imageId)
    {
        $room = Room::find($roomId);

        if (!$room) {
            return $this->apiError('Kamar tidak ditemukan', 404);
        }

        $image = $room->images()->find($imageId);

        if (!$image) {
            return $this->apiError('Foto tidak ditemukan', 404);
        }

        $image->delete();

        return $this->apiSuccess(null, 'Foto berhasil dihapus');
    }
}
