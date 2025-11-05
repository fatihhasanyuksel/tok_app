<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    protected $table = 'threads';

    protected $fillable = [
        'submission_id',
        'selection_text',
        'start_offset',
        'end_offset',
        'pm_from',
        'pm_to',
        'is_resolved',
    ];

    protected $casts = [
        'start_offset' => 'integer',
        'end_offset'   => 'integer',
        'pm_from'      => 'integer',
        'pm_to'        => 'integer',
        'is_resolved'  => 'boolean',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];
}