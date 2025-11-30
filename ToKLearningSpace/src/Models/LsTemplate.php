<?php

namespace ToKLearningSpace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LsTemplate extends Model
{
    use HasFactory;

    protected $table = 'tok_ls_templates';

    protected $fillable = [
        'title',
        'topic',
        'duration_minutes',
        'objectives',
        'success_criteria',
        'content_html',
        'content_text',
        'notes',
        'is_published',
        'created_by',
        'updated_by',
    ];

    // Creator (teacher/admin)
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // Last updater
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}