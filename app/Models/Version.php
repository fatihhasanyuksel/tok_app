<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Version extends Model
{
    // Option A: allow all fields (simplest)
    // protected $guarded = [];

    // Option B: explicit allow-list (keep this if you prefer fillable)
    protected $fillable = [
        'submission_id',
        'body_html',
        'files_json',
        'is_milestone',
        'milestone_note',
        'created_by',   // ✅ new
    ];

    protected $casts = [
        'files_json'   => 'array',
        'is_milestone' => 'boolean',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    // Convenience: the student via the submission
    public function student()
    {
        return $this->submission?->student();
    }

    // ✅ Who created this version (teacher / student / admin)
    public function author()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}