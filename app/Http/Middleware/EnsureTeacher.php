<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Teacher;

class EnsureTeacher
{
    /**
     * Allow only teacher or admin roles and ensure a valid Teacher model is available.
     * - Resolves teacher via session('teacher_id') or Auth::user()->email
     * - Ensures teacher is active (and is_admin when user role=admin)
     * - Sets request attribute 'teacher' and persists session('teacher_id')
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Must be logged-in with an allowed role
        if (!$user || !in_array($user->role, ['teacher', 'admin'], true)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Access denied — only teachers and admins may enter this area.',
            ]);
        }

        // 1) Try legacy session bridge
        $teacher = null;
        if ($id = $request->session()->get('teacher_id')) {
            $teacher = Teacher::find($id);
        }

        // 2) If not found, resolve from unified Auth user by email
        if (!$teacher && !empty($user->email)) {
            $teacher = Teacher::where('email', $user->email)->first();
            if ($teacher) {
                $request->session()->put('teacher_id', $teacher->id);
            }
        }

        // 3) Validate teacher record
        if (!$teacher || !$teacher->active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your teacher account is not active. Please contact an administrator.',
            ]);
        }

        // If user role is admin, enforce teacher.is_admin as well
        if ($user->role === 'admin' && !$teacher->is_admin) {
            abort(403, 'Admins only.');
        }

        // 4) Make it available to controllers that read request()->attributes->get('teacher')
        $request->attributes->set('teacher', $teacher);

        return $next($request);
    }
}