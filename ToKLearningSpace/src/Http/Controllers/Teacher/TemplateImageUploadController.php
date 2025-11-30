<?php

namespace ToKLearningSpace\Http\Controllers\Teacher;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class TemplateImageUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp|max:4096', // 4MB
        ]);

        $user = auth()->user();

        if (! in_array($user->role, ['teacher', 'admin'], true)) {
            abort(403, 'Unauthorized');
        }

        $file = $request->file('image');

        // 1 — Load using Intervention
        $manager = new ImageManager(new Driver());
        $image   = $manager->read($file->getRealPath());

        // 2 — Resize (max 1600px)
        $maxDim = 1600;
        if ($image->width() > $maxDim || $image->height() > $maxDim) {
            $image->scaleDown($maxDim, $maxDim);
        }

        // 3 — Convert → WebP & hash
        $webp   = $image->toWebp(80);
        $sha    = sha1($webp);
        $fileName = $sha . '.webp';

        $path = "tok-ls/{$user->id}/template-images/{$fileName}";

        // 4 — Save if not exists (dedupe)
        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, $webp);
        }

        // 5 — Respond URL for TipTap
        return response()->json([
            'url' => asset("storage/{$path}"),
        ]);
    }
}