<?php

namespace ToKLearningSpace\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class LsResponse extends Model
{
    protected $table = 'tok_ls_responses';

    protected $fillable = [
        'lesson_id',
        'student_id',
        'student_response',
        'teacher_feedback',
    ];

    public function lesson()
    {
        return $this->belongsTo(LsLesson::class, 'lesson_id');
    }

    public function student()
    {
        // Assuming your main User model is App\Models\User
        return $this->belongsTo(User::class, 'student_id');
    }
}