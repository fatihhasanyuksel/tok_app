<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckpointStatus extends Model
{
    protected $table = 'checkpoint_statuses';

    // Use the real column names from your table
    protected $fillable = [
        'student_id',
        'type',          // exhibition | essay
        'status_code',   // Draft1 | Draft2 | FinalSubmitted | Approved | ...
        'note',
        'selected_by',
        'selected_at',
    ];

    protected $casts = [
        'selected_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function selectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by');
    }
}