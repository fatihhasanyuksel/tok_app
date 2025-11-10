<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    protected $fillable = [
        'student_id',
        'type',        // 'exhibition' | 'essay'
        'status',      // 'draft' | 'submitted' | 'changes' | 'final'
        'due_at',
    ];

    protected $casts = [
        'due_at'      => 'datetime',
        'working_rev' => 'integer',
    ];

    // Who owns this submission (the student)
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // All frozen snapshots
    public function versions(): HasMany
    {
        return $this->hasMany(Version::class)->orderBy('created_at', 'asc');
    }

    // Latest version convenience
    public function latestVersion()
    {
        return $this->hasOne(Version::class)->latestOfMany();
    }

    // General messages (for the new inbox/slide-over feature)
    public function generalMessages(): HasMany
    {
        return $this->hasMany(\App\Models\GeneralMessage::class);
    }
}