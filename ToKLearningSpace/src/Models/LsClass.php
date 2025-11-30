<?php

namespace ToKLearningSpace\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class LsClass extends Model
{
    // Explicit table name (matches your LS migrations)
    protected $table = 'tok_ls_classes';

    protected $fillable = [
        'teacher_id',
        'name',
        'year',
        'archived_at',   // allow mass assignment
    ];

    // Cast archived_at to a Carbon datetime
    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Students in this class (pivot: ls_class_student)
    public function students()
    {
        return $this->belongsToMany(
            User::class,
            'ls_class_student', // pivot table
            'ls_class_id',      // FK pointing to this model
            'student_id'        // FK pointing to users.id
        )->withTimestamps();
    }

    // Lessons / units for this class
    public function lessons()
    {
        return $this->hasMany(LsLesson::class, 'class_id');
    }

    // Scope: only active (non-archived) classes
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    // Used by Blade to show archive state
    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }
}