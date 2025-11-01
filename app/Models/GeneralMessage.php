<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeneralMessage extends Model
{
    protected $fillable = [
        'submission_id',   // FK -> submissions.id
        'sender_id',       // FK -> users.id
        'body',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ---------- Relationships ----------

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(GeneralMessageRead::class, 'message_id');
    }

    // ---------- Helpers / Scopes ----------

    /** Return true if the given user has a read receipt for this message. */
    public function isReadBy(?User $user): bool
    {
        if (!$user) return false;
        // in-memory check if loaded, otherwise fallback to exists() query
        if ($this->relationLoaded('reads')) {
            return $this->reads->firstWhere('user_id', $user->id)?->read_at !== null;
        }
        return $this->reads()->where('user_id', $user->id)->whereNotNull('read_at')->exists();
    }

    /** Mark this message as read for the given user (idempotent). */
    public function markReadFor(User $user): void
    {
        $this->reads()->updateOrCreate(
            ['user_id' => $user->id],
            ['read_at' => now()]
        );
    }

    /** Unread count for a user within this message’s submission (useful if eager-loading). */
    public function scopeUnreadFor($query, User $user)
    {
        return $query->whereDoesntHave('reads', function ($q) use ($user) {
            $q->where('user_id', $user->id)->whereNotNull('read_at');
        });
    }
}