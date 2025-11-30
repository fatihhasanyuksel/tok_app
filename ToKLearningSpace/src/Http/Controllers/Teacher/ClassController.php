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
            ->whereNull('archived_at')              // ← hide archived classes
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

    // Archive (soft hide) a class
    public function archive(LsClass $class)
    {
        // Later we can re-enable strict ownership checks if needed:
        // if ($class->teacher_id !== auth()->id()) abort(403);

        if ($class->archived_at) {
            return redirect()
                ->route('tok-ls.teacher.classes.show', $class->id)
                ->with('success', 'Class is already archived.');
        }

        $class->archived_at = now();
        $class->save();

        return redirect()
            ->route('tok-ls.teacher.classes')
            ->with('success', 'Class archived. You can restore it later from the archived list.');
    }

    public function destroy(LsClass $class)
    {
        // TEMPORARY: allow delete for MVP.
        // Later will enforce ownership checks + soft delete rules.

        $class->delete();

        return redirect()
            ->route('tok-ls.teacher.classes')
            ->with('success', 'Class deleted.');
    }

    // Show "Add Students" page
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

    // Handle POST for attaching selected students
    public function storeStudents(LsClass $class, Request $request)
    {
        // Validate incoming IDs
        $data = $request->validate([
            'student_ids'   => 'required|array',
            'student_ids.*' => 'integer|exists:users,id',
        ]);

        // Attach without removing existing ones, ignore duplicates
        $class->students()->syncWithoutDetaching($data['student_ids']);

        return redirect()
            ->route('tok-ls.teacher.classes.show', $class->id)
            ->with('success', 'Students added successfully.');
    }

    // Remove a single student from the class
    public function removeStudent(LsClass $class, User $student)
    {
        // Later ownership checks may return
        $class->students()->detach($student->id);

        return redirect()
            ->route('tok-ls.teacher.classes.show', $class->id)
            ->with('success', 'Student removed from class.');
    }

    // List archived classes
    public function archived(Request $request)
    {
        $classes = LsClass::query()
            ->whereNotNull('archived_at')
            ->orderBy('archived_at', 'desc')
            ->get();

        return view('tok_ls::teacher.classes.archived', [
            'classes' => $classes,
        ]);
    }

    // NEW: Unarchive a class
    public function unarchive(LsClass $class)
    {
        // Restore visibility
        $class->archived_at = null;
        $class->save();

        return redirect()
            ->route('tok-ls.teacher.classes.archived')
            ->with('success', 'Class has been unarchived.');
    }
}