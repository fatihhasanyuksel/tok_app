<?php

namespace ToKLearningSpace\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class LsClass extends Model
{
    // Explicit table name (matches your migration)
    protected $table = 'tok_ls_classes';

    protected $fillable = [
        'teacher_id',
        'name',
        'year',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // 🔹 Students in this class (pivot: ls_class_student)
    public function students()
    {
        return $this->belongsToMany(
            User::class,
            'ls_class_student', // pivot table
            'ls_class_id',      // FK pointing to this model
            'student_id'        // FK pointing to users.id
        )->withTimestamps();
    }

    // 🔹 Lessons / units for this class (uses class_id in tok_ls_lessons)
    public function lessons()
    {
        return $this->hasMany(LsLesson::class, 'class_id');
    }
}