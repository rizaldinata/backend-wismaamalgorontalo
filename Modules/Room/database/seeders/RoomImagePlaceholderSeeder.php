<?php

namespace Modules\Room\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class RoomImagePlaceholderSeeder extends Seeder
{
    /**
     * Generate placeholder images untuk room dummy
     */
    public function run(): void
    {
        // Pastikan direktori rooms & thumbs ada
        if (! Storage::disk('public')->exists('rooms/thumbs')) {
            Storage::disk('public')->makeDirectory('rooms/thumbs');
        }

        $rooms = \Modules\Room\Models\Room::with('images')->get();

        // Get all dummy images
        $dummyImagesPath = __DIR__ . '/dummy_images';
        $dummyImages = [];
        if (is_dir($dummyImagesPath)) {
            $dummyImages = glob($dummyImagesPath . '/*.*');
        }

        foreach ($rooms as $room) {
            foreach ($room->images as $image) {
                $filename = basename($image->image_path);
                $path = storage_path('app/public/rooms/'.$filename);
                $thumbPath = storage_path('app/public/rooms/thumbs/'.$filename);

                // Update database if thumbnail_path is null
                if (! $image->thumbnail_path) {
                    $image->update(['thumbnail_path' => 'rooms/thumbs/'.$filename]);
                }

                // Skip jika file gambar utama sudah ada dan tidak kosong (0 bytes)
                if (file_exists($path) && filesize($path) > 0) {
                    // Cek thumbnail juga
                    if (! file_exists($thumbPath) || filesize($thumbPath) == 0) {
                        $this->copyPlaceholder($thumbPath, $dummyImages);
                    }

                    continue;
                }

                // Generate Main Image
                $this->copyPlaceholder($path, $dummyImages);

                // Generate Thumbnail
                $this->copyPlaceholder($thumbPath, $dummyImages);
            }
        }

        $this->command->info('✅ Berhasil menyalin dummy images & thumbnails untuk rooms!');
    }

    private function copyPlaceholder($path, $dummyImages)
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (count($dummyImages) > 0) {
            // Ambil gambar acak dari folder dummy_images
            $randomImage = $dummyImages[array_rand($dummyImages)];
            copy($randomImage, $path);
        } else {
            // Fallback jika tidak ada gambar dummy
            file_put_contents($path, '');
        }
    }
}
