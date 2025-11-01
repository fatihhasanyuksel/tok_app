<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\Student;

class AdminController extends Controller
{
    /**
     * Admin landing dashboard (Phase 5)
     */
    public function dashboard()
    {
        // Minimal landing view for now
        // Later we can add stats: total students, active teachers, etc.
        return view('admin.dashboard');
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