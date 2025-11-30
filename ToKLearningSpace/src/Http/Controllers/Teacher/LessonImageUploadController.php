<?php

namespace ToKLearningSpace\Http\Controllers\Teacher;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class LessonImageUploadController extends Controller
{
    /**
     * Handle teacher image uploads for lessons.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:4096', // 4MB
        ]);

        $user = auth()->user();

        // Extra safeguard: must be teacher/admin
        if (! in_array($user->role, ['teacher', 'admin'], true)) {
            abort(403, 'Unauthorized');
        }

        $file = $request->file('image');

        // ---------------------------------------
        // Step 1 — Load image using Intervention
        // ---------------------------------------
        $manager = new ImageManager(new Driver());
        $image   = $manager->read($file->getRealPath());

        // ---------------------------------------
        // Step 2 — Resize if needed (max 1600px)
        // ---------------------------------------
        $w = $image->width();
        $h = $image->height();
        $maxDim = 1600;

        if ($w > $maxDim || $h > $maxDim) {
            $image->scaleDown($maxDim, $maxDim);
        }

        // ---------------------------------------
        // Step 3 — Convert to WebP & hash filename
        // ---------------------------------------
        $webpBytes = $image->toWebp(80);

        // same image bytes = same hash = same filename
        $sha      = sha1($webpBytes);
        $fileName = $sha . '.webp';

        $path = "tok-ls/{$user->id}/lesson-images/{$fileName}";

        // ---------------------------------------
        // Step 4 — Store (public disk) if not present
        // ---------------------------------------
        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, $webpBytes);
        }

        // ---------------------------------------
        // Step 5 — Return URL for TipTap
        // ---------------------------------------
        return response()->json([
            'url' => asset("storage/{$path}"),
        ]);
    }
}