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
        'status',          // open | seen | revised | approved | outdated | reopened
        'selection_text',  // optional preview of selected text
        // Note: we are NOT mass-assigning is_resolved yet; we’ll set it explicitly in controller.
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
    ];

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

    // The single anchor for this thread (we keep one active anchor per thread)
    public function anchor(): HasOne
    {
        return $this->hasOne(CommentAnchor::class);
    }

    // Messages in the thread (ordered oldest → newest)
    public function messages(): HasMany
    {
        return $this->hasMany(CommentMessage::class)->orderBy('created_at', 'asc');
    }

    // The latest message (for previews in the thread list)
    public function latestMessage(): HasOne
    {
        return $this->hasOne(CommentMessage::class)->latestOfMany();
    }

    // Event timeline (created, seen, replied, revised, approved, reopened, outdated)
    public function events(): HasMany
    {
        return $this->hasMany(CommentEvent::class)->orderBy('created_at', 'asc');
    }
}