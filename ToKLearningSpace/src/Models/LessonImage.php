<?php

namespace ToKLearningSpace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use ToKLearningSpace\Models\LsLesson;   // ← required model

class LessonImage extends Model
{
    protected $table = 'tok_ls_lesson_images';

    protected $fillable = [
        'lesson_id',
        'path',
    ];

    public function lesson()
    {
        return $this->belongsTo(LsLesson::class, 'lesson_id');
    }

    /**
     * When a LessonImage row is deleted, also delete the underlying file.
     */
    protected static function booted()
    {
        static::deleting(function (LessonImage $image) {
            if ($image->path && Storage::disk('public')->exists($image->path)) {
                Storage::disk('public')->delete($image->path);
            }
        });
    }
}