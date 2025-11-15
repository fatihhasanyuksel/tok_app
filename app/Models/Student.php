<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'teacher_id',
        'parent_name',
        'parent_email',
        'parent_phone',
    ];

    /** A student can have many reflections. */
    public function reflections()
    {
        return $this->hasMany(\App\Models\Reflection::class);
    }

    /** Each student belongs to one teacher. */
    public function teacher()
    {
        // uses teacher_id by convention
        return $this->belongsTo(\App\Models\Teacher::class);
    }

    /** Convenience: "First Last" */
    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** ✅ New: canonical label for the supervisor pill */
    public function getSupervisorLabelAttribute(): string
    {
        // Teachers table has a single `name` column
        return $this->teacher?->name ?? 'Unassigned';
    }
}