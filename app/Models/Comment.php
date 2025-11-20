<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    protected $fillable = [
        'version_id',
        'author_id',
        'selection_text',   // preview of selected text
        'start_offset',     // plain-text start offset
        'end_offset',       // plain-text end offset
        'pm_from',          // ProseMirror absolute start pos
        'pm_to',            // ProseMirror absolute end pos
        'is_resolved',      // kept mass-assignable for convenience
    ];

    protected $casts = [
        'start_offset' => 'integer',
        'end_offset'   => 'integer',
        'pm_from'      => 'integer',
        'pm_to'        => 'integer',
        'is_resolved'  => 'boolean',
    ];

    /**
     * Relationships
     */

    // Thread belongs to a specific frozen version
    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }

    // Author (teacher or student)
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // Messages in this thread (ordered oldest → newest)
    public function messages(): HasMany
    {
        return $this->hasMany(CommentMessage::class, 'comment_id', 'id')
            ->orderBy('created_at', 'asc');
    }

    // Latest message (for previews in the thread list)
    public function latestMessage(): HasOne
    {
        return $this->hasOne(CommentMessage::class, 'comment_id', 'id')
            ->latestOfMany()
            ->select('comment_messages.*');
    }

    // Event timeline (created, replied, resolved, etc.)
    public function events(): HasMany
    {
        return $this->hasMany(CommentEvent::class, 'comment_id', 'id')
            ->orderBy('created_at', 'asc');
    }
}