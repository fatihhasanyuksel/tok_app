<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; // ✅ added

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = Teacher::orderBy('name')->paginate(20);
        return view('admin.teachers.index', compact('teachers'));
    }

    public function create()
    {
        return view('admin.teachers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:150'],
            'email'    => ['required','email','max:255','unique:teachers,email'],
            'password' => ['required','string','min:8'],
            'active'   => ['nullable','boolean'],
            'is_admin' => ['nullable','boolean'],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['active']   = (bool)($data['active'] ?? false);
        $data['is_admin'] = (bool)($data['is_admin'] ?? false);

        Teacher::create($data);

        return redirect()->route('admin.teachers.index')->with('ok', 'Teacher created.');
    }

    public function edit(Teacher $teacher)
    {
        return view('admin.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:150'],
            'email'    => ['required','email','max:255','unique:teachers,email,'.$teacher->id],
            'password' => ['nullable','string','min:8'],
            'active'   => ['nullable','boolean'],
            'is_admin' => ['nullable','boolean'],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['active']   = (bool)($data['active'] ?? false);
        $data['is_admin'] = (bool)($data['is_admin'] ?? false);

        $teacher->update($data);

        return redirect()->route('admin.teachers.index')->with('ok', 'Teacher updated.');
    }

    public function destroy(Teacher $teacher)
    {
        // ✅ Prevent deleting yourself (compare authenticated user email to teacher email)
        $auth = Auth::user();
        if ($auth && strcasecmp($auth->email, $teacher->email) === 0) {
            return back()->withErrors(['delete' => 'You cannot delete your own teacher record while logged in.']);
        }

        $teacher->delete();

        return redirect()->route('admin.teachers.index')->with('ok', 'Teacher deleted.');
    }

    public function resetPassword(Request $request, Teacher $teacher)
    {
        $data = $request->validate([
            'password' => ['required','string','min:8']
        ]);

        $teacher->update(['password' => Hash::make($data['password'])]);

        return back()->with('ok', 'Password reset.');
    }
}