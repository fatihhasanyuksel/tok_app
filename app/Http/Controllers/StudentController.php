<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Teacher;
use App\Models\Student;

class StudentController extends Controller
{
    /**
     * Resolve the current teacher from:
     *   (1) request attribute 'teacher'
     *   (2) session('teacher_id')
     *   (3) Auth::user()->email -> Teacher
     * Persists attribute + session when resolved.
     */
    private function resolveTeacher(Request $request): ?Teacher
    {
        if ($t = $request->attributes->get('teacher')) {
            return $t instanceof Teacher ? $t : null;
        }

        if ($id = $request->session()->get('teacher_id')) {
            if ($t = Teacher::find($id)) {
                $request->attributes->set('teacher', $t);
                return $t;
            }
        }

        if (Auth::check()) {
            $email = Auth::user()->email ?? null;
            if ($email) {
                if ($t = Teacher::where('email', $email)->first()) {
                    $request->session()->put('teacher_id', $t->id);
                    $request->attributes->set('teacher', $t);
                    return $t;
                }
            }
        }

        return null;
    }

    public function index(Request $request)
    {
        $teacher = $this->resolveTeacher($request);
        if (!$teacher || !$teacher->active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please log in as a teacher/admin.',
            ]);
        }

        $students = Student::where('teacher_id', $teacher->id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20);

        return view('students.index', compact('students'));
    }

    public function create(Request $request)
    {
        $teacher = $this->resolveTeacher($request);
        if (!$teacher || !$teacher->active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please log in as a teacher/admin.',
            ]);
        }

        return view('students.create');
    }

    public function store(Request $request)
    {
        $teacher = $this->resolveTeacher($request);
        if (!$teacher || !$teacher->active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please log in as a teacher/admin.',
            ]);
        }

        $data = $request->validate([
            'first_name'   => ['required','string','max:100'],
            'last_name'    => ['required','string','max:100'],
            'email'        => ['nullable','email','max:190','unique:students,email'],
            'parent_email' => ['nullable','email','max:190'],
            'parent_phone' => ['nullable','string','max:50'],
        ]);

        $data['teacher_id'] = $teacher->id;

        Student::create($data);

        return redirect()->route('students.index')->with('ok', 'Student added.');
    }

    public function show(Request $request, Student $student)
    {
        $teacher = $this->resolveTeacher($request);
        if (!$teacher || !$teacher->active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please log in as a teacher/admin.',
            ]);
        }

        abort_unless($student->teacher_id === $teacher->id, 403);

        $student->load('reflections');
        return view('students.show', compact('student'));
    }

    public function edit(Request $request, Student $student)
    {
        $teacher = $this->resolveTeacher($request);
        if (!$teacher || !$teacher->active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please log in as a teacher/admin.',
            ]);
        }

        abort_unless($student->teacher_id === $teacher->id, 403);

        return view('students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $teacher = $this->resolveTeacher($request);
        if (!$teacher || !$teacher->active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please log in as a teacher/admin.',
            ]);
        }

        abort_unless($student->teacher_id === $teacher->id, 403);

        $data = $request->validate([
            'first_name'   => ['required','string','max:100'],
            'last_name'    => ['required','string','max:100'],
            'email'        => ['nullable','email','max:190','unique:students,email,'.$student->id],
            'parent_email' => ['nullable','email','max:190'],
            'parent_phone' => ['nullable','string','max:50'],
        ]);

        $student->update($data);

        return redirect()->route('students.index')->with('ok', 'Student updated.');
    }

    public function destroy(Request $request, Student $student)
    {
        $teacher = $this->resolveTeacher($request);
        if (!$teacher || !$teacher->active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please log in as a teacher/admin.',
            ]);
        }

        abort_unless($student->teacher_id === $teacher->id, 403);

        $student->delete();

        return redirect()->route('students.index')->with('ok', 'Deleted.');
    }
}