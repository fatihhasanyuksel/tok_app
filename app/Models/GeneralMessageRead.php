<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneralMessageRead extends Model
{
    protected $fillable = [
        'message_id',
        'user_id',
        'read_at',
    ];

    /**
     * Disable Laravel’s automatic timestamps.
     * Our table does not have created_at / updated_at columns.
     */
    public $timestamps = false;

    public function message(): BelongsTo
    {
        return $this->belongsTo(GeneralMessage::class, 'message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}