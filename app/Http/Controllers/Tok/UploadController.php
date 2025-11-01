<?php

namespace App\Http\Controllers\Tok;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * POST /api/tok/uploads/images
     * FormData: file (image), owner_type, owner_id
     * Returns: { url, width, height }
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'file'       => 'required|file|mimetypes:image/jpeg,image/png,image/webp|max:4096', // 4 MB
            'owner_type' => 'required|string|in:exhibition,essay',
            'owner_id'   => 'required|integer|min:1',
        ]);

        $this->authorizeAccess($data['owner_type'], (int)$data['owner_id']);

        $file = $data['file'];
        $ext  = strtolower($file->extension() ?: $file->guessExtension() ?: 'jpg');

        if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
            // Normalize uncommon extension guesses
            $ext = 'jpg';
        }

        $filename = Str::uuid()->toString() . '.' . $ext;
        $dir = "tok/{$data['owner_type']}/{$data['owner_id']}/images";

        // Save to the public disk so it serves from /storage/...
        $path = Storage::disk('public')->putFileAs($dir, $file, $filename);

        // Attempt to read dimensions (safe fallback if unsupported)
        $width = $height = null;
        if (function_exists('getimagesize')) {
            try {
                $info = @getimagesize($file->getPathname());
                if (is_array($info)) {
                    $width  = $info[0] ?? null;
                    $height = $info[1] ?? null;
                }
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'url'    => asset('storage/' . $path),
            'width'  => $width,
            'height' => $height,
        ], 201);
    }

    /**
     * Phase-1 placeholder: require a logged-in user.
     * Replace with real owner/teacher policy later.
     */
    protected function authorizeAccess(string $ownerType, int $ownerId): void
    {
        if (!Auth::check()) {
            abort(401, 'Unauthenticated');
        }
    }
}
