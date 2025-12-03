<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ToKLearningSpace\Models\LsClass;
use ToKLearningSpace\Models\LsLesson;
use ToKLearningSpace\Models\LsResponse;
use App\Models\User;

class TlsClassController extends Controller
{
    /**
     * Admin view: list all ToK Learning Space classes.
     */
    public function index()
    {
        // Get all classes, newest first, with student counts
        $classes = LsClass::query()
            ->withCount('students')              // uses LsClass::students() relation
            ->orderBy('created_at', 'desc')
            ->get();

        // Map teacher_id -> teacher name (from users table)
        $teacherNames = User::whereIn('id', $classes->pluck('teacher_id')->filter()->all())
            ->pluck('name', 'id');

        return view('admin.tls.classes.index', [
            'classes'      => $classes,
            'teacherNames' => $teacherNames,
        ]);
    }

    /**
     * Admin view: show a single TLS class + student metrics panel.
     */
    public function show(Request $request, int $id)
    {
        // Load the class + its students
        $class = LsClass::with('students')->findOrFail($id);

        $selectedStudent = null;
        $studentMetrics  = null;

        // Student is passed as a query param: /admin/learning-space/classes/2?student=15
        $selectedId = (int) $request->query('student');

        if ($selectedId) {
            // Only allow students that actually belong to this class
            $selectedStudent = $class->students->firstWhere('id', $selectedId);

            if ($selectedStudent) {
                $studentId = $selectedStudent->id;

                // --- LESSONS ASSIGNED (TLS) ---
                // Count TLS lessons that target this class.
                $lessonsAssigned = LsLesson::where('class_id', $class->id)->count();

                // --- RESPONSES / ACTIVITY (TLS) ---
                // All responses by this student for lessons in this class
                $responsesBase = LsResponse::where('student_id', $studentId)
                    ->whereHas('lesson', function ($q) use ($class) {
                        $q->where('class_id', $class->id);
                    });

                // Distinct lessons where student has responded at least once
                $lessonsWithResponses = (clone $responsesBase)
                    ->distinct('lesson_id')
                    ->count('lesson_id');

                // Total number of responses in this class
                $totalResponses = (clone $responsesBase)->count();

                // Last TLS activity = latest updated_at among responses
                $lastActivity = (clone $responsesBase)->max('updated_at');

                $lastActivityFormatted = $lastActivity
                    ? \Carbon\Carbon::parse($lastActivity)->format('Y-m-d H:i')
                    : null;

                $studentMetrics = [
                    'lessons_assigned'    => $lessonsAssigned,
                    // We keep the existing keys so the Blade view doesn't break:
                    'lessons_opened'      => $lessonsWithResponses,
                    'responses_submitted' => $totalResponses,
                    'last_activity'       => $lastActivityFormatted,
                ];
            }
        }

        return view('admin.tls.classes.show', [
            'class'           => $class,
            'selectedStudent' => $selectedStudent,
            'studentMetrics'  => $studentMetrics,
        ]);
    }

    /**
     * Admin view: read-only list of lessons in this class for a given student.
     *
     * Route:
     *  GET /admin/learning-space/classes/{class}/students/{student}/lessons
     *  Name: tok-ls.admin.classes.student-lessons
     */
    public function studentLessons(int $classId, int $studentId)
    {
        // Load the class with its students
        $class = LsClass::with('students')->findOrFail($classId);

        // Make sure this student actually belongs to the class
        $student = $class->students->firstWhere('id', $studentId);
        if (! $student) {
            abort(404);
        }

        // Safe, narrow version:
        // Admin sees the same set of TLS lessons assigned to this class.
        $lessons = LsLesson::where('class_id', $class->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.tls.classes.student_lessons', [
            'class'   => $class,
            'student' => $student,
            'lessons' => $lessons,
        ]);
    }

    /**
     * Admin view: show a single lesson for this student (read-only).
     *
     * Route:
     *  GET /admin/learning-space/classes/{class}/students/{student}/lessons/{lesson}
     *  Name: tok-ls.admin.classes.student-lesson-show
     */
    public function studentLessonShow(int $classId, int $studentId, int $lessonId)
    {
        // Load the class with its students
        $class = LsClass::with('students')->findOrFail($classId);

        // Ensure the student actually belongs to this class
        $student = $class->students->firstWhere('id', $studentId);
        if (! $student) {
            abort(404);
        }

        // Ensure the lesson belongs to this class
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        // Get this student's latest response (if any) for this lesson
        $response = LsResponse::where('lesson_id', $lesson->id)
            ->where('student_id', $student->id)
            ->latest('updated_at')
            ->first();

        return view('admin.tls.classes.student_lesson_show', [
            'class'    => $class,
            'student'  => $student,
            'lesson'   => $lesson,
            'response' => $response,
        ]);
    }
}