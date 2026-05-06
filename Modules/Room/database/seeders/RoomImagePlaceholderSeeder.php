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
        if (!Storage::disk('public')->exists('rooms/thumbs')) {
            Storage::disk('public')->makeDirectory('rooms/thumbs');
        }

        $rooms = \Modules\Room\Models\Room::with('images')->get();

        foreach ($rooms as $room) {
            foreach ($room->images as $image) {
                $filename = basename($image->image_path);
                $path = storage_path('app/public/rooms/' . $filename);
                $thumbPath = storage_path('app/public/rooms/thumbs/' . $filename);

                // Update database if thumbnail_path is null
                if (!$image->thumbnail_path) {
                    $image->update(['thumbnail_path' => 'rooms/thumbs/' . $filename]);
                }

                // Skip jika file gambar utama sudah ada
                if (file_exists($path)) {
                    // Cek thumbnail juga
                    if (!file_exists($thumbPath)) {
                        $this->generatePlaceholder($thumbPath, $room->number, "Thumb", 400, 300);
                    }
                    continue;
                }

                // Generate Main Image
                $this->generatePlaceholder($path, $room->number, "Image", 800, 600);

                // Generate Thumbnail
                $this->generatePlaceholder($thumbPath, $room->number, "Thumb", 400, 300);
            }
        }

        $this->command->info('✅ Berhasil generate placeholder images & thumbnails untuk rooms!');
    }

    private function generatePlaceholder($path, $text1, $text2, $width, $height)
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (function_exists('imagecreatetruecolor')) {
            $img = \imagecreatetruecolor($width, $height);
            $bgColor = \imagecolorallocate($img, 240, 240, 240);
            \imagefill($img, 0, 0, $bgColor);
            $textColor = \imagecolorallocate($img, 100, 100, 100);

            \imagestring($img, 5, ($width / 2) - 50, ($height / 2) - 20, "Room " . $text1, $textColor);
            \imagestring($img, 4, ($width / 2) - 40, ($height / 2) + 10, $text2, $textColor);

            \imagejpeg($img, $path, 80);
            \imagedestroy($img);
        } else {
            // Fallback if GD is not installed
            file_put_contents($path, "");
        }
    }
}
