<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\User; // 👈 ADD THIS

class AdminController extends Controller
{
    /**
     * Admin landing dashboard (Phase 5)
     */
    public function dashboard(Request $request)
    {
        // Load students in a DB-safe way
        $students = Student::orderBy('id')->get();

        $selectedStudent = null;
        $selectedUserId  = null; // 👈 this will become the ?student= value

        if ($request->filled('student_id')) {
            $selectedStudent = Student::find($request->student_id);

            if ($selectedStudent) {
                // Prefer an explicit mapping if it exists
                if (!empty($selectedStudent->user_id)) {
                    $selectedUserId = (int) $selectedStudent->user_id;
                } else {
                    // Fallback: resolve the corresponding user via email
                    $selectedUserId = User::where('email', $selectedStudent->email)->value('id');
                }
            }
        }

        return view('admin.dashboard', [
            'students'        => $students,
            'selectedStudent' => $selectedStudent,
            'selectedUserId'  => $selectedUserId, // 👈 pass to blade
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
            ->with('ok_transfer', "Transferred {$count} student(s)."); // ✅ scoped flash key
    }
}