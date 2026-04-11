<?php

namespace Modules\Maintenance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    /**
     * Serve a file from storage with explicit CORS headers.
     * Useful for Flutter Web (CanvasKit) image loading.
     */
    public function show(string $path)
    {
        // Path might contain multiple segments, e.g., maintenance/img.jpg
        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $file = Storage::disk('public')->get($path);
        $type = Storage::disk('public')->mimeType($path);

        return response($file, 200)
            ->header('Content-Type', $type)
            // Explicitly allow all origins for media requests to fix Flutter Web CORS issues
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET')
            ->header('Cache-Control', 'public, max-age=31536000');
    }
}
