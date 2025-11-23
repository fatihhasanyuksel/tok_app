<?php

namespace ToKLearningSpace\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use ToKLearningSpace\Models\LsClass;
use ToKLearningSpace\Models\LsLesson;

class ClassController extends Controller
{
    public function show(LsClass $class)
    {
        $user = auth()->user();

        // Ensure the logged-in student actually belongs to this class
        $belongs = $class->students()
            ->where('users.id', $user->id)
            ->exists();

        if (! $belongs) {
            abort(403, 'You are not assigned to this class.');
        }

        // Only show PUBLISHED lessons to students
        $lessons = LsLesson::where('class_id', $class->id)
            ->whereNotNull('published_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('tok_ls::student.classes.show', [
            'class'   => $class,
            'lessons' => $lessons,
        ]);
    }
}