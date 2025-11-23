<?php

namespace ToKLearningSpace\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ToKLearningSpace\Models\LsClass;
use ToKLearningSpace\Models\LsLesson;
use ToKLearningSpace\Models\LsResponse;

class LessonController extends Controller
{
    // -------------------------------------------------
    // 1) Show all lessons for a class
    // -------------------------------------------------
    public function index(LsClass $class)
    {
        $class->load(['lessons' => function ($q) {
            $q->orderBy('created_at', 'desc');
        }]);

        return view('tok_ls::teacher.lessons.index', [
            'class'   => $class,
            'lessons' => $class->lessons,
        ]);
    }

    // -------------------------------------------------
    // 2) Show Create Lesson form
    // -------------------------------------------------
    public function createForm(LsClass $class)
    {
        return view('tok_ls::teacher.lessons.create', [
            'class' => $class,
        ]);
    }

    // -------------------------------------------------
    // 3) Store new lesson
    // -------------------------------------------------
    public function store(LsClass $class, Request $request)
    {
        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'nullable|string',
            'status'  => 'required|in:draft,published',
        ]);

        $lesson = LsLesson::create([
            'class_id'     => $class->id,
            'teacher_id'   => auth()->id(),
            'title'        => $validated['title'],
            'content'      => $validated['content'] ?? null,
            'status'       => $validated['status'],
            'published_at' => $validated['status'] === 'published' ? now() : null,
        ]);

        return redirect()
            ->route('tok-ls.teacher.lessons.show', [$class->id, $lesson->id])
            ->with('success', 'Lesson created successfully.');
    }

    // -------------------------------------------------
    // 4) Show lesson details + student responses overview
    // -------------------------------------------------
    public function show(LsClass $class, $lessonId, Request $request)
    {
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        $students = $class->students()
            ->orderBy('name')
            ->get();

        $responsesByStudent = LsResponse::where('lesson_id', $lesson->id)
            ->get()
            ->keyBy('student_id');

        $selectedStudentId = $request->query('student');
        $selectedStudent   = $selectedStudentId
            ? $students->firstWhere('id', (int) $selectedStudentId)
            : null;

        $selectedResponse  = $selectedStudent
            ? $responsesByStudent->get($selectedStudent->id)
            : null;

        $responses = $responsesByStudent->values();

        return view('tok_ls::teacher.lessons.show', [
            'class'              => $class,
            'lesson'             => $lesson,
            'students'           => $students,
            'responsesByStudent' => $responsesByStudent,
            'selectedStudent'    => $selectedStudent,
            'selectedResponse'   => $selectedResponse,
            'responses'          => $responses,
        ]);
    }

    // -------------------------------------------------
    // 5) Edit Lesson (GET)
    // -------------------------------------------------
    public function editForm(LsClass $class, $lessonId)
    {
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        return view('tok_ls::teacher.lessons.edit', [
            'class'  => $class,
            'lesson' => $lesson,
        ]);
    }

    // -------------------------------------------------
    // 6) Update lesson
    // -------------------------------------------------
    public function update(LsClass $class, $lessonId, Request $request)
    {
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'nullable|string',
            'status'  => 'required|in:draft,published',
        ]);

        $lesson->title   = $validated['title'];
        $lesson->content = $validated['content'] ?? null;
        $lesson->status  = $validated['status'];

        if ($validated['status'] === 'published' && !$lesson->published_at) {
            $lesson->published_at = now();
        }
        if ($validated['status'] === 'draft') {
            $lesson->published_at = null;
        }

        $lesson->save();

        return redirect()
            ->route('tok-ls.teacher.lessons.show', [$class->id, $lesson->id])
            ->with('success', 'Lesson updated successfully.');
    }

    // -------------------------------------------------
    // 7) Delete lesson
    // -------------------------------------------------
    public function delete(LsClass $class, $lessonId)
    {
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        $lesson->delete();

        return redirect()
            ->route('tok-ls.teacher.lessons.index', $class->id)
            ->with('success', 'Lesson deleted.');
    }

    // -------------------------------------------------
    // 8) Show single student response + feedback form
    // -------------------------------------------------
    public function showFeedback(LsClass $class, $lessonId, $responseId)
    {
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        $response = LsResponse::where('id', $responseId)
            ->where('lesson_id', $lesson->id)
            ->with('student')
            ->firstOrFail();

        $student = $response->student;

        return view('tok_ls::teacher.lessons.feedback', [
            'class'    => $class,
            'lesson'   => $lesson,
            'response' => $response,
            'student'  => $student,
        ]);
    }

    // -------------------------------------------------
    // 9) Autosave teacher feedback (AJAX / JSON)
    // -------------------------------------------------
    public function autosaveFeedback(LsClass $class, $lessonId, $responseId, Request $request)
    {
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        $validated = $request->validate([
            'teacher_feedback' => 'nullable|string',
        ]);

        $response = LsResponse::where('id', $responseId)
            ->where('lesson_id', $lesson->id)
            ->firstOrFail();

        $response->teacher_feedback = $validated['teacher_feedback'] ?? null;
        $response->save();

        return response()->json([
            'status'   => 'ok',
            'saved_at' => now()->toDateTimeString(),
        ]);
    }

    // -------------------------------------------------
    // 10) Toggle publish / unpublish
    // -------------------------------------------------
    public function togglePublish(LsClass $class, $lessonId)
    {
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        if ($lesson->published_at) {
            $lesson->published_at = null;
            $lesson->status       = 'draft';
            $message = 'Lesson unpublished. It is now hidden from students.';
        } else {
            $lesson->published_at = now();
            $lesson->status       = 'published';
            $message = 'Lesson published. It is now visible to students.';
        }

        $lesson->save();

        return redirect()
            ->route('tok-ls.teacher.lessons.show', [$class->id, $lesson->id])
            ->with('success', $message);
    }
}