<?php

namespace ToKLearningSpace\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ToKLearningSpace\Models\LsClass;
use App\Models\User;

class ClassController extends Controller
{

    public function index()
    {
        $classes = LsClass::query()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('tok_ls::teacher.classes.index', [
            'classes' => $classes,
        ]);
    }

    // Show the "Create Class" page
    public function createForm()
    {
        return view('tok_ls::teacher.classes.create');
    }

    // Handle POST: Save class (Year removed)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        LsClass::create([
            'teacher_id' => auth()->id(),
            'name'       => $validated['name'],
            'year'       => null,
        ]);

        return redirect()->route('tok-ls.teacher.classes');
    }

    // View a single class with Blade view
    public function show(LsClass $class)
    {
        // TEMPORARILY DISABLED — re-enable later
        // if ($class->teacher_id !== auth()->id()) abort(403);

        // eager-load students, sorted by name
        $class->load(['students' => function ($q) {
            $q->orderBy('name');
        }]);

        return view('tok_ls::teacher.classes.show', [
            'class' => $class,
        ]);
    }

public function destroy(LsClass $class)
{
    // TEMPORARY: allow delete for MVP, even if teacher_id is null / mismatched.
    // Later, when all existing classes have correct teacher_id and we filter
    // index() by the logged-in teacher, we re-enable this:
    //
    // if ($class->teacher_id !== auth()->id()) {
    //     abort(403, 'Not authorized to delete this class.');
    // }

    $class->delete();

    return redirect()
        ->route('tok-ls.teacher.classes')
        ->with('success', 'Class deleted.');
}

    // 🔹 Step 2.7.3 — Show "Add Students" page with real users + search
    public function addStudents(LsClass $class, Request $request)
    {
        $search = trim((string) $request->input('q'));

        // Only show STUDENTS
        $query = User::query()
            ->where('role', 'student')
            ->orderBy('name');

        // Optional search filter
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $students = $query->limit(200)->get();

        return view('tok_ls::teacher.classes.add-students', [
            'class'    => $class,
            'students' => $students,
            'search'   => $search,
        ]);
    }

    // 🔹 Step 2.7.4 — Handle POST for attaching selected students
    public function storeStudents(LsClass $class, Request $request)
    {
        // Validate incoming IDs
        $data = $request->validate([
            'student_ids'   => 'required|array',
            'student_ids.*' => 'integer|exists:users,id',
        ]);

        // Attach students without removing existing ones, ignore duplicates
        $class->students()->syncWithoutDetaching($data['student_ids']);

        // Redirect back to class detail page with a small success message
        return redirect()
            ->route('tok-ls.teacher.classes.show', $class->id)
            ->with('success', 'Students added successfully.');
    }

    // 🔹 NEW — Remove a single student from the class
    public function removeStudent(LsClass $class, User $student)
    {
        // Later we can re-enable a strict ownership check:
        // if ($class->teacher_id !== auth()->id()) {
        //     abort(403);
        // }

        // Detach this student from the pivot table
        $class->students()->detach($student->id);

        return redirect()
            ->route('tok-ls.teacher.classes.show', $class->id)
            ->with('success', 'Student removed from class.');
    }
}