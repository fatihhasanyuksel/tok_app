<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Teacher;

class EnsureRole
{
    /**
     * Allow if:
     *  - Auth user has role in $roles, OR
     *  - Legacy Teacher row exists & is active:
     *      - any teacher role allowed if 'teacher' is in $roles
     *      - teacher->is_admin === true allowed if 'admin' is in $roles
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Must be logged in
        $user = Auth::user();
        if (!$user) {
            // consistent message on login screen
            return redirect()->route('login')
                ->with('error', 'Access denied — required role: ' . implode(' or ', $roles));
        }

        // 1) Primary: unified users.role
        $role = $user->role ? strtolower((string) $user->role) : null;
        if ($role && in_array($role, $roles, true)) {
            return $next($request);
        }

        // 2) Legacy fallback: teachers table by email
        $teacher = Teacher::where('email', $user->email)->first();
        if ($teacher && (int)$teacher->active === 1) {
            // Allow teacher if requested
            if (in_array('teacher', $roles, true)) {
                return $next($request);
            }
            // Allow admin if requested and legacy admin flag set
            if ($teacher->is_admin && in_array('admin', $roles, true)) {
                return $next($request);
            }
        }

        // Deny with clear 403 (not a redirect loop back to login)
        abort(403, 'Access denied — required role: ' . implode(' or ', $roles));
    }
}