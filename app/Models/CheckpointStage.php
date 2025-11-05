<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckpointStage extends Model
{
    protected $table = 'checkpoint_stages';
    public $timestamps = false;

    protected $fillable = [
        'key',        // string, unique machine key e.g. 'draft_1'
        'label',      // string, human label e.g. 'Draft 1'
        'display_order', // int, nullable
        'is_active',     // bool, default true
    ];

    // Optional helpers (safe even if you don't use them)
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeOrdered($q) { return $q->orderBy('display_order')->orderBy('id'); }
}