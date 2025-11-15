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
     * Returns: { url, width, height, path }
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'file'       => 'required|file|mimetypes:image/jpeg,image/png,image/webp|max:5120', // 5 MB
            'owner_type' => 'required|string|in:exhibition,essay',
            'owner_id'   => 'required|integer|min:1',
        ]);

        $this->authorizeAccess($data['owner_type'], (int) $data['owner_id']);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');

        // --- decide extension safely
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'jpg';
        }

        // --- fingerprinted filename + dated subfolders
        $ym       = now()->format('Y/m');
        $uuid     = Str::uuid()->toString();               // unique per upload
        $filename = "{$uuid}.{$ext}";
        $dir      = "tok/images/{$data['owner_type']}/{$data['owner_id']}/{$ym}";

        // Save to the "public" disk => storage/app/public/... (symlinked to /public/storage)
        $path = Storage::disk('public')->putFileAs($dir, $file, $filename);

        // Read dimensions (best-effort)
        $width = $height = null;
        if (function_exists('getimagesize')) {
            try {
                $info = @getimagesize($file->getPathname());
                if (is_array($info)) {
                    $width  = $info[0] ?? null;
                    $height = $info[1] ?? null;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return response()->json([
            'url'   => Storage::disk('public')->url($path), // e.g., /storage/...
            'width' => $width,
            'height'=> $height,
            'path'  => $path,                                // optional housekeeping
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
        // Future: check ownership/role for $ownerType/$ownerId
    }
}