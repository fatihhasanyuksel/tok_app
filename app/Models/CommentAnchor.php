<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentAnchor extends Model
{
    protected $fillable = [
        'comment_id',
        'start_offset',
        'end_offset',
        'before_hash',
        'after_hash',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }
}