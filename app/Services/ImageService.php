<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Upload and compress image to WebP format
     */
    public function uploadAndCompress(UploadedFile $file, string $folder, int $width = 1200, int $quality = 75): string
    {
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . uniqid() . '.webp';
        $path = "$folder/$filename";

        // Create directory if not exists
        if (!Storage::disk('public')->exists($folder)) {
            Storage::disk('public')->makeDirectory($folder);
        }

        try {
            $image = $this->manager->read($file);
            
            // Resize if wider than max width
            $image->scaleDown(width: $width);

            // Encode to WebP
            $encoded = $image->toWebp($quality);

            Storage::disk('public')->put($path, (string) $encoded);
        } catch (\Exception $e) {
            // Fallback to original if processing fails (e.g. missing GD)
            return $file->store($folder, 'public');
        }

        return $path;
    }

    /**
     * Create a thumbnail in WebP format
     */
    public function createThumbnail(UploadedFile $file, string $folder, int $size = 300, int $quality = 70): string
    {
        $filename = 'thumb_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . uniqid() . '.webp';
        $path = "$folder/$filename";

        if (!Storage::disk('public')->exists($folder)) {
            Storage::disk('public')->makeDirectory($folder);
        }

        try {
            $image = $this->manager->read($file);
            $image->cover($size, $size);
            $encoded = $image->toWebp($quality);

            Storage::disk('public')->put($path, (string) $encoded);
        } catch (\Exception $e) {
            // If failed, just use original file but as thumbnail (not ideal but safe)
            return $file->store($folder, 'public');
        }

        return $path;
    }
}
