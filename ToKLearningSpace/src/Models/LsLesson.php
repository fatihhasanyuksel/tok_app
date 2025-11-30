<?php

namespace ToKLearningSpace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LsLesson extends Model
{
    use HasFactory;

    protected $table = 'tok_ls_lessons';

    protected $fillable = [
        'class_id',
        'teacher_id',
        'title',
        'slug',
        'content',
        'status',
        'published_at',

        // Extra fields
        'objectives',
        'success_criteria',
        'duration_minutes',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function class()
    {
        return $this->belongsTo(LsClass::class, 'class_id');
    }

    public function responses()
    {
        return $this->hasMany(LsResponse::class, 'lesson_id');
    }
}