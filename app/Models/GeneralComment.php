<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneralComment extends Model
{
    protected $fillable = [
        'version_id',
        'author_id',
        'body',
        'read_at_by_student',
    ];

    protected $casts = [
        'read_at_by_student' => 'datetime',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}