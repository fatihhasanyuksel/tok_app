<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tok\RichTextController;
use App\Http\Controllers\Tok\UploadController;
use App\Http\Controllers\ThreadPositionsController;
use App\Http\Controllers\Tok\DocController;

/*
|--------------------------------------------------------------------------
| ToK API Routes
|--------------------------------------------------------------------------
| Legacy endpoints remain. The new revision-aware autosave lives under
| /api/tok/rev/docs/* and uses session ("web") auth so students/teachers
| can call it with normal cookies.
*/

/* ─────────────────────────────────────────────
 | RICH TEXT (LEGACY AUTOSAVE + COMMIT)
 |─────────────────────────────────────────────*/
Route::get('/tok/docs/{owner_type}/{owner_id}', [RichTextController::class, 'show']);
Route::match(['PATCH', 'POST'], '/tok/docs/{owner_type}/{owner_id}', [RichTextController::class, 'autosave']);
Route::post('/tok/docs/{owner_type}/{owner_id}/commit', [RichTextController::class, 'commit']);

/* ─────────────────────────────────────────────
 | AUTOSAVE (REVISION-AWARE + LEGACY-COMPATIBLE)
 | Uses session auth -> ['web','auth']
 |─────────────────────────────────────────────*/
Route::middleware(['web', 'auth'])->group(function () {

    // Legacy-compatible (no revision conflict checks)
    Route::patch('/tok/docs/{ownerType}/{ownerId}', [DocController::class, 'patch'])
        ->name('tok.docs.patch');

    // Revision-aware (409 on mismatch)
    Route::patch('/tok/rev/docs/{ownerType}/{ownerId}', [DocController::class, 'patchRev'])
        ->name('tok.docs.patchRev');
});

/* ─────────────────────────────────────────────
 | IMAGE UPLOADS
 |─────────────────────────────────────────────*/
Route::post('/tok/uploads/images', [UploadController::class, 'store'])
    ->name('tok.uploads.images');

/* ─────────────────────────────────────────────
 | COMMENT HIGHLIGHT POSITIONS (single source)
 |─────────────────────────────────────────────*/
Route::patch('/threads/{thread}/positions', [ThreadPositionsController::class, 'update'])
    ->name('threads.positions.update');

/* ─────────────────────────────────────────────
 | TEST / PING
 |─────────────────────────────────────────────*/
Route::get('/threads/ping', fn () => response()->json(['ok' => true]))
    ->name('threads.ping');