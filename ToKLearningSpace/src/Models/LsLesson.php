<?php

namespace ToKLearningSpace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LsLesson extends Model
{
    use HasFactory;

    // Explicit table name to match our migration
    protected $table = 'tok_ls_lessons';

    protected $fillable = [
        'class_id',
        'teacher_id',   // ✅ allow mass-assignment
        'title',
        'slug',
        'content',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * The class this lesson belongs to.
     */
    public function class()
    {
        return $this->belongsTo(LsClass::class, 'class_id');
    }

    /**
     * Placeholder for responses; we’ll wire this up later.
     */
    public function responses()
    {
        return $this->hasMany(LsResponse::class, 'lesson_id');
    }
}