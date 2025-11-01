<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMood extends Model
{
    protected $fillable = [
        'student_id',
        'submission_id',
        'mood',  // confident | calm | uncertain | stressed
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}