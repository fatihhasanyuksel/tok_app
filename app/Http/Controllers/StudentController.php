<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\CheckpointStatus;
use App\Models\User;
use App\Services\StudentMetrics;

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

        // Teacher’s own students (paginated)
        $students = Student::where('teacher_id', $teacher->id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20);

        $pageStudents = $students->getCollection(); // Eloquent\Collection of current page
        $pageIds      = $pageStudents->pluck('id');

        // Resolve user_ids for each student on this page (for workspace + metrics)
        $studentUserIds = [];
        foreach ($pageStudents as $s) {
            $uid = $s->user_id ?? null;

            if (!$uid && $s->email) {
                $uid = User::where('email', $s->email)->value('id');
            }

            // Make sure StudentMetrics sees the resolved user ID
            $s->user_id = $uid;
            $studentUserIds[$s->id] = $uid;
        }

        // ✅ Fetch checkpoint statuses only for the students on this page
        $statuses = $pageIds->isNotEmpty()
            ? CheckpointStatus::whereIn('student_id', $pageIds)->get()->groupBy('student_id')
            : collect();

        // ✅ Load stages once here (no DB/model calls in Blade)
        $stages = DB::table('checkpoint_stages')
            ->select('key', 'label')
            ->where('is_active', true)
            ->orderByRaw('COALESCE(display_order, 9999)')
            ->get();

        // ✅ Status meta (last updated + who) for each (student_id, type)
        $metaRows = $pageIds->isNotEmpty()
            ? DB::table('checkpoint_statuses as cs')
                ->leftJoin('users as u', 'u.id', '=', 'cs.selected_by')
                ->whereIn('cs.student_id', $pageIds)
                ->select('cs.student_id', 'cs.type', 'cs.status_code', 'cs.selected_at', 'u.name as selected_by_name')
                ->get()
            : collect();

        $statusMeta = $metaRows
            ->groupBy('student_id')
            ->map(function ($group) {
                return $group->keyBy('type')->map(function ($row) {
                    return [
                        'status_code'      => $row->status_code,
                        'selected_at'      => $row->selected_at,
                        'selected_by_name' => $row->selected_by_name,
                    ];
                })->toArray();
            })->toArray();

        // ✅ NEW: full metrics bundle per student (same engine as Admin dashboard)
        $metricsService   = app(StudentMetrics::class);
        $metricsByStudent = [];

        foreach ($pageStudents as $s) {
            $bundle = $metricsService->buildForStudent($s);

            // Override with our resolved user ID (same trick as AdminController)
            $bundle['selectedUserId'] = $studentUserIds[$s->id] ?? ($bundle['selectedUserId'] ?? null);

            $metricsByStudent[$s->id] = $bundle;
        }

        return view('students.index', [
            'students'        => $students,
            'statuses'        => $statuses,
            'stages'          => $stages,         // for dropdowns
            'statusMeta'      => $statusMeta,     // for “Updated … by …”
            'metricsByStudent'=> $metricsByStudent,
            'studentUserIds'  => $studentUserIds, // for workspace links
        ]);
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