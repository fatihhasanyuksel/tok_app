<?php

namespace App\Models;

use App\Enums\TaskType;
use App\Enums\StatusStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CheckpointDeadline extends Model
{
    protected $table = 'checkpoint_deadlines';

    protected $fillable = [
        'task_type',
        'stage',
        'due_at',
        'label',
    ];

    protected $casts = [
        'task_type' => TaskType::class,
        'stage'     => StatusStage::class,
        'due_at'    => 'datetime',
    ];

    // --- Scopes ---
    public function scopeForTask(Builder $q, TaskType $task): Builder
    {
        return $q->where('task_type', $task);
    }

    public function scopeStage(Builder $q, StatusStage $stage): Builder
    {
        return $q->where('stage', $stage);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        // order by stage enum value then date (sane default)
        return $q->orderBy('stage')->orderBy('due_at');
    }
}