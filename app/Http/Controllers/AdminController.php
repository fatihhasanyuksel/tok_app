<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentMetrics;
use App\Services\TeacherMetrics;

class AdminController extends Controller
{
    /**
     * Admin landing dashboard (Phase 5)
     */
    public function dashboard(Request $request)
    {
        // Load students and their linked user accounts (if any)
        $students = Student::with('user')
            ->orderBy('id')
            ->get();

        // Sort alphabetically by name (this used to be in the Blade)
        $students = $students->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        // Selection state
        $selectedStudent = null;
        $selectedUserId  = null;

        if ($request->filled('student_id')) {
            $selectedStudent = Student::with('user')->find($request->student_id);

            if ($selectedStudent) {
                // 1) Prefer the explicit relationship
                if ($selectedStudent->user) {
                    $selectedUserId = (int) $selectedStudent->user->id;
                } elseif (!empty($selectedStudent->user_id)) {
                    // 2) Fallback if relation isn't loaded for some reason
                    $selectedUserId = (int) $selectedStudent->user_id;
                } else {
                    // 3) Last-resort fallback: resolve via email (very rare now)
                    $selectedUserId = User::where('email', $selectedStudent->email)->value('id');
                }

                // Ensure the StudentMetrics service sees the resolved user ID
                // (this mirrors the old behaviour where we queried by $selectedUserId)
                if ($selectedUserId) {
                    $selectedStudent->user_id = $selectedUserId;
                }
            }
        }

        // Unified metrics service: handles progress + writing metrics
        /** @var \App\Services\StudentMetrics $metricsService */
        $metricsService = app(StudentMetrics::class);
        $bundle         = $metricsService->buildForStudent($selectedStudent);

        // Override selectedUserId in the bundle with the controller’s resolved value
        $bundle['selectedUserId'] = $selectedUserId;

        // Global teacher metrics (pulled from shared service)
        /** @var \App\Services\TeacherMetrics $teacherMetricsService */
        $teacherMetricsService = app(TeacherMetrics::class);
        $teacherMetrics        = $teacherMetricsService->getGlobalMetrics();

        return view('admin.dashboard', [
            'students'        => $students,
            'selectedStudent' => $bundle['selectedStudent'],
            'progress'        => $bundle['progress'],
            'studentMetrics'  => $bundle['studentMetrics'],
            'selectedUserId'  => $bundle['selectedUserId'],
            'teacherMetrics'  => $teacherMetrics,
        ]);
    }

    /**
     * Show transfer form (move students from one teacher to another)
     */
    public function transferForm(Request $request)
    {
        $teachers = Teacher::orderBy('name')->get();
        $students = Student::orderBy('id')->get();

        return view('admin.transfer', compact('teachers', 'students'));
    }

    /**
     * Process transfer form submission
     */
    public function transferDo(Request $request)
    {
        $data = $request->validate([
            'to_teacher_id'   => ['required', 'integer', 'exists:teachers,id'],
            'student_ids'     => ['required', 'array', 'min:1'],
            'student_ids.*'   => ['integer', 'exists:students,id'],
        ]);

        $count = Student::whereIn('id', $data['student_ids'])
            ->update(['teacher_id' => $data['to_teacher_id']]);

        return redirect()
            ->route('admin.transfer')
            ->with('ok_transfer', "Transferred {$count} student(s).");
    }
}