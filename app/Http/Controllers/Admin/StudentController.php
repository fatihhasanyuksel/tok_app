<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;

class StudentController extends Controller
{
    /**
     * Split a full name into [first_name, last_name].
     */
    private function splitFullName(string $full): array
    {
        $full = trim(preg_replace('/\s+/', ' ', $full));
        if ($full === '') return ['', ''];
        $parts = explode(' ', $full);
        if (count($parts) === 1) return [$parts[0], ''];
        $last  = array_pop($parts);
        $first = implode(' ', $parts);
        return [$first, $last];
    }

    /**
     * List all students for Admin — keyed by students.id.
     * Reads from legacy students table, joins users (by email) for user_id
     * and teachers for teacher name. Returns s.id as id to match route model binding.
     */
    public function index(Request $request)
    {
        $q         = trim((string) $request->query('q', ''));
        $teacherId = $request->query('teacher'); // teachers.id (legacy students.teacher_id)

        $query = DB::table('students as s')
            ->leftJoin('users as u', 'u.email', '=', 's.email')          // expose linked user_id
            ->leftJoin('teachers as tt', 'tt.id', '=', 's.teacher_id')   // teacher display name
            ->when($teacherId, fn ($qry) => $qry->where('s.teacher_id', $teacherId))
            ->when($q !== '', function ($qry) use ($q) {
                $like = "%{$q}%";
                $qry->where(function ($w) use ($like) {
                    $w->where('s.first_name', 'like', $like)
                      ->orWhere('s.last_name', 'like', $like)
                      ->orWhere(DB::raw("CONCAT(s.first_name, ' ', s.last_name)"), 'like', $like)
                      ->orWhere('s.email', 'like', $like)
                      ->orWhere('s.id', 'like', $like);
                });
            })
            ->orderBy('s.id', 'asc');

        // select the Student ID explicitly as `id`
        $students = $query->paginate(25, [
            's.id as id',
            's.first_name',
            's.last_name',
            's.email',
            's.teacher_id',
            DB::raw('tt.name as teacher_name'),
            DB::raw('u.id as user_id'),
        ])->withQueryString();

        $teachers = Teacher::orderBy('name')->get(['id','name']);

        return view('admin.students.index', [
            'students'         => $students,
            'teachers'         => $teachers,
            'q'                => $q,
            'filterTeacherId'  => $teacherId,
        ]);
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $teachers = Teacher::orderBy('name')->get(['id','name']);
        return view('admin.students.create', compact('teachers'));
    }

    /**
     * Store a new student (and ensure a users row with role=student).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'email', 'max:255'],
            'password'          => ['nullable', 'string', 'min:6'],
            'assign_to'         => ['nullable', 'integer', 'exists:teachers,id'],
            'generate_password' => ['nullable', 'boolean'],

            // ✅ Parent contacts (optional)
            'parent_name'       => ['nullable', 'string', 'max:255'],
            'parent_email'      => ['nullable', 'email',  'max:255'],
            'parent_phone'      => ['nullable', 'string', 'max:50'],
        ]);

        $plain = null;
        if (!empty($data['password'])) {
            $plain = $data['password'];
        } elseif (!empty($data['generate_password'])) {
            $plain = Str::password(12);
        }

        $passwordForCreate = Hash::make($plain ?: Str::password(12));

        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'name'     => $data['name'],
                'role'     => 'student',
                'password' => $passwordForCreate,
            ]
        );

        if ($user->wasRecentlyCreated === false) {
            $user->name = $data['name'];
            $user->role = 'student';
            if ($plain) {
                $user->password = Hash::make($plain);
            }
            $user->save();
        }

        [$first, $last] = $this->splitFullName($data['name']);
        Student::updateOrCreate(
            ['email' => $data['email']],
            [
                'first_name'  => $first,
                'last_name'   => $last,
                'teacher_id'  => $data['assign_to'] ?? null,

                // ✅ write parent contacts
                'parent_name'  => $data['parent_name']  ?? null,
                'parent_email' => $data['parent_email'] ?? null,
                'parent_phone' => $data['parent_phone'] ?? null,
            ]
        );

        $msg = "Student '{$data['name']}' created.";
        if ($plain) $msg .= " Password set.";

        return redirect()
            ->route('admin.students.index')
            ->with('ok_students', $msg)
            ->with('generated_password_students', $plain);
    }

    /**
     * Edit form.
     */
    public function edit(Student $student)
    {
        $teachers = Teacher::orderBy('name')->get(['id','name']);
        return view('admin.students.edit', compact('student', 'teachers'));
    }

    /**
     * Update an existing student (and optional password reset).
     */
    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'email', 'max:255'],
            'password'          => ['nullable', 'string', 'min:6'],
            'assign_to'         => ['nullable', 'integer', 'exists:teachers,id'],
            'generate_password' => ['nullable', 'boolean'],

            // ✅ Parent contacts (optional)
            'parent_name'       => ['nullable', 'string', 'max:255'],
            'parent_email'      => ['nullable', 'email',  'max:255'],
            'parent_phone'      => ['nullable', 'string', 'max:50'],
        ]);

        [$first, $last] = $this->splitFullName($data['name']);
        $student->first_name  = $first;
        $student->last_name   = $last;
        $student->email       = $data['email'];
        $student->teacher_id  = $data['assign_to'] ?? null;

        // ✅ save parent contacts
        $student->parent_name  = $data['parent_name']  ?? null;
        $student->parent_email = $data['parent_email'] ?? null;
        $student->parent_phone = $data['parent_phone'] ?? null;

        $student->save();

        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'name'     => $data['name'],
                'role'     => 'student',
                'password' => Hash::make(Str::password(12)),
            ]
        );

        $plain = null;
        if (!empty($data['password'])) {
            $plain = $data['password'];
        } elseif (!empty($data['generate_password'])) {
            $plain = Str::password(12);
        }
        if ($plain) {
            $user->password = Hash::make($plain);
        }
        $user->name = $data['name'];
        $user->role = 'student';
        $user->save();

        $msg = "Student '{$data['name']}' updated.";
        if ($plain) $msg .= " Password reset.";

        return redirect()
            ->route('admin.students.edit', $student->id)
            ->with('ok_edit', $msg)
            ->with('generated_password_students', $plain);
    }

    /**
     * Destroy student (keep users row for now).
     */
    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()
            ->route('admin.students.index')
            ->with('ok_students', "Student deleted.");
    }

    /**
     * Dedicated password reset endpoint.
     */
    public function resetPassword(Student $student, Request $request)
    {
        $request->validate([
            'password' => ['nullable', 'string', 'min:6'],
            'generate' => ['nullable', 'boolean'],
        ]);

        $plain = $request->input('password');
        if (!$plain && $request->boolean('generate')) {
            $plain = Str::password(12);
        }
        if (!$plain) {
            return back()->withErrors(['password' => 'Provide a password or choose Generate.']);
        }

        $user = User::firstOrCreate(
            ['email' => $student->email],
            [
                'name'     => trim($student->first_name.' '.$student->last_name) ?: $student->email,
                'role'     => 'student',
                'password' => Hash::make(Str::password(12)),
            ]
        );

        $user->password = Hash::make($plain);
        $user->save();

        return back()
            ->with('ok_students', 'Password reset.')
            ->with('generated_password_students', $plain);
    }
}