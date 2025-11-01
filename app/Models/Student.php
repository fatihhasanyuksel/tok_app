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
        'teacher_id',      // link each student to a teacher
        'parent_name',     // ✅ new: parent's full name
        'parent_email',    // ✅ new: parent's email
        'parent_phone',    // ✅ new: parent's phone number
    ];

    /**
     * Relationship: a student can have many reflections.
     */
    public function reflections()
    {
        return $this->hasMany(\App\Models\Reflection::class);
    }

    /**
     * Relationship: each student belongs to one teacher.
     */
    public function teacher()
    {
        return $this->belongsTo(\App\Models\Teacher::class);
    }

    /**
     * Accessor: combine first and last name for convenience.
     */
    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}