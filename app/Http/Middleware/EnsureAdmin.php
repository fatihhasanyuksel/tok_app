<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Teacher;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $id = $request->session()->get('teacher_id');

        // ✅ If no teacher_id in session, redirect to unified login
        if (!$id) {
            return redirect()->route('login')->with('ok', 'Please log in.');
        }

        $teacher = Teacher::find($id);

        // ✅ Ensure user is an active admin
        if (!$teacher || !$teacher->active || !$teacher->is_admin) {
            abort(403, 'Admins only.');
        }

        // Optionally store teacher in request for controllers or views
        $request->attributes->set('teacher', $teacher);

        return $next($request);
    }
}