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
        'is_milestone',   // NEW
        'milestone_note', // NEW
    ];

    protected $casts = [
        'files_json'    => 'array',
        'is_milestone'  => 'boolean', // NEW
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
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
}