<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ResourcesController extends Controller
{
    /**
     * Student-facing: list resources.
     */
    public function index()
    {
        [$files] = $this->listFiles();
        return view('resources.index', [
            'files' => $files,
        ]);
    }

    /**
     * Teacher-facing: manage resources (upload/delete).
     * Note: Route already protected by EnsureTeacher in web.php.
     */
    public function manage()
    {
        [$files, $dir] = $this->listFiles();
        return view('resources.manage', [
            'files' => $files,
            'dir'   => $dir,
        ]);
    }

    /**
     * Upload a new resource (teacher-only).
     */
    public function upload(Request $request)
    {
        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'max:30720', // 30 MB
            ],
        ]);

        $file     = $data['file'];
        $origName = $file->getClientOriginalName();
        $safeName = $this->sanitizeFilename($origName);

        // Use the public disk and the 'tok' directory
        $disk = Storage::disk('public');
        $dir  = 'tok';
        $pathCheck = $dir . '/' . $safeName;

        // Avoid overwriting: if exists, append -1, -2, ...
        if ($disk->exists($pathCheck)) {
            $safeName = $this->uniquifyFilenameOnDisk($disk, $dir, $safeName);
        }

        // Store the file to storage/app/public/tok
        $disk->putFileAs($dir, $file, $safeName);

        return redirect()
            ->route('resources.manage')
            ->with('ok', 'Uploaded: ' . $safeName);
    }

    /**
     * Delete a resource (teacher-only).
     */
    public function destroy(Request $request, string $filename)
    {
        $disk = Storage::disk('public');
        $dir  = 'tok';
        $name = $this->sanitizeFilename($filename);

        $path = $dir . '/' . $name;
        if (!$disk->exists($path)) {
            return redirect()
                ->route('resources.manage')
                ->with('error', 'File not found: ' . $name);
        }

        $disk->delete($path);

        return redirect()
            ->route('resources.manage')
            ->with('ok', 'Deleted: ' . $name);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers

    /**
     * Build file list from storage/app/public/tok -> /storage/tok/*
     * @return array [files(array), dir(string)]
     */
    private function listFiles(): array
    {
        $disk = Storage::disk('public');
        $dir  = 'tok';
        $publicUrlPrefix = '/storage/tok/';

        // Ensure directory exists
        if (!$disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        $files = [];
        foreach ($disk->files($dir) as $path) {
            // $path like "tok/filename.ext"
            $name = basename($path);
            $size = $disk->size($path);
            $time = $disk->lastModified($path);

            $files[] = [
                'name'    => $name,
                'url'     => $publicUrlPrefix . $name,
                'size'    => $this->formatBytes($size),
                'updated' => Carbon::createFromTimestamp($time)->diffForHumans(),
            ];
        }

        // Sort by name (asc)
        usort($files, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return [$files, $dir];
    }

    private function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow   = $bytes > 0 ? (int)floor(log($bytes, 1024)) : 0;
        $pow   = min($pow, count($units) - 1);

        $bytes = $bytes / (1 << (10 * $pow)); // divide by 1024^pow
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    private function sanitizeFilename(string $name): string
    {
        // Keep extension, clean base
        $ext  = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);

        $base = Str::of($base)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9\-\_\.]+/', '-')
            ->trim('-_')
            ->lower();

        return $ext ? "{$base}.{$ext}" : $base;
    }

    /**
     * Ensure a unique filename on the given disk/dir (adds -1, -2, ...)
     */
    private function uniquifyFilenameOnDisk($disk, string $dir, string $filename): string
    {
        $ext  = pathinfo($filename, PATHINFO_EXTENSION);
        $base = pathinfo($filename, PATHINFO_FILENAME);

        $n = 1;
        do {
            $candidate = $ext ? "{$base}-{$n}.{$ext}" : "{$base}-{$n}";
            $path = $dir . '/' . $candidate;
            $n++;
        } while ($disk->exists($path));

        return $candidate;
    }
}