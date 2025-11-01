<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reflection;

class ReflectionController extends Controller
{
    /** List reflections for the logged-in teacher */
    public function index(Request $request)
    {
        $teacherId = (int) $request->session()->get('teacher_id');

        $reflections = Reflection::forTeacher($teacherId)
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('reflections.index', compact('reflections'));
    }

    /** Show create form */
    public function create()
    {
        return view('reflections.create');
    }

    /** Store new reflection */
    public function store(Request $request)
    {
        $teacherId = (int) $request->session()->get('teacher_id');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body'  => ['nullable', 'string'],
        ]);

        Reflection::create([
            'teacher_id' => $teacherId,
            'title'      => $data['title'],
            'body'       => $data['body'] ?? null,
            'status'     => 'draft',
        ]);

        return redirect()->route('reflections.index')->with('ok', 'Reflection created.');
    }

    /** Show edit form */
    public function edit(Request $request, Reflection $reflection)
    {
        $this->ensureOwner($request, $reflection);
        return view('reflections.edit', compact('reflection'));
    }

    /** Update reflection */
    public function update(Request $request, Reflection $reflection)
    {
        $this->ensureOwner($request, $reflection);

        $data = $request->validate([
            'title'  => ['required', 'string', 'max:200'],
            'body'   => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:30'],
        ]);

        $reflection->update([
            'title'  => $data['title'],
            'body'   => $data['body'] ?? null,
            'status' => $data['status'] ?? $reflection->status,
        ]);

        return redirect()->route('reflections.index')->with('ok', 'Reflection updated.');
    }

    /** Delete reflection */
    public function destroy(Request $request, Reflection $reflection)
    {
        $this->ensureOwner($request, $reflection);
        $reflection->delete();

        return redirect()->route('reflections.index')->with('ok', 'Reflection deleted.');
    }

    /** Owner check; adopt orphaned records (NULL teacher_id) */
    private function ensureOwner(Request $request, Reflection $reflection): void
    {
        $teacherId = (int) $request->session()->get('teacher_id');
        if (!$teacherId) abort(401);

        if (is_null($reflection->teacher_id)) {
            $reflection->teacher_id = $teacherId;
            $reflection->save();
        }

        if ((int) $reflection->teacher_id !== $teacherId) {
            abort(403);
        }
    }
}