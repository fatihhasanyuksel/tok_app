<?php

namespace ToKLearningSpace\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ToKLearningSpace\Models\LsClass;
use ToKLearningSpace\Models\LsLesson;
use ToKLearningSpace\Models\LsResponse;

class LessonResponseController extends Controller
{
    /**
     * Show the 3-box lesson page for a student:
     * 1) Lesson content
     * 2) Student response
     * 3) Teacher feedback (if any)
     */
    public function showForm(LsClass $class, $lessonId)
    {
        // Make sure lesson belongs to this class
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        $studentId = auth()->id();

        // tok_ls_responses has: lesson_id, student_id, student_response, teacher_feedback
        $response = LsResponse::where('lesson_id', $lesson->id)
            ->where('student_id', $studentId)
            ->first();

        return view('tok_ls::student.lessons.respond', [
            'class'    => $class,
            'lesson'   => $lesson,
            'response' => $response,
        ]);
    }

    /**
     * Save / update the student's response.
     */
    public function saveResponse(LsClass $class, $lessonId, Request $request)
    {
        // Enforce that lesson belongs to the given class
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        $studentId = auth()->id();

        $validated = $request->validate([
            'student_response' => 'nullable|string',
        ]);

        // Use lesson_id + student_id as the unique key
        $response = LsResponse::firstOrNew([
            'lesson_id'  => $lesson->id,
            'student_id' => $studentId,
        ]);

        $response->student_response = $validated['student_response'] ?? null;
        $response->save();

        return redirect()
            ->route('tok-ls.student.lessons.respond', [$class->id, $lesson->id])
            ->with('success', 'Your response has been saved.');
    }
}