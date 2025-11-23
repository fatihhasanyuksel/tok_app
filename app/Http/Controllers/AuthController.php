<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
use App\Models\Teacher;

class AuthController extends Controller
{
    /**
     * Add strict no-store headers to responses that render forms,
     * to avoid stale CSRF tokens being cached by the browser/proxy.
     */
    private function noStore($response)
    {
        return $response
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function showLogin(Request $request)
    {
        $roles = ['student', 'teacher', 'admin'];

        // Wrap the view in no-store headers (prevents intermittent 419s)
        return $this->noStore(
            response()->view('auth.login', [
                'go'    => $request->query('go'),
                'roles' => $roles,
            ])
        );
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
            'role'     => ['required', 'in:student,teacher,admin'],
            'go'       => ['nullable', 'string'],
            'remember' => ['nullable'],
        ]);

        $email    = strtolower(trim($data['email']));
        $plain    = $data['password'];
        $roleWant = $data['role'];
        $go       = $data['go'] ?? null;
        $remember = (bool)($data['remember'] ?? false);

        // If we already have a users row with a role, enforce role consistency up front.
        if ($existing = User::where('email', $email)->first()) {
            if (!empty($existing->role) && $existing->role !== $roleWant) {
                return back()
                    ->withErrors([
                        'email' => "This account is '{$existing->role}'. You selected '{$roleWant}'.",
                    ])
                    ->withInput($request->only('email'));
            }

            // Repair legacy rows with NULL password so Auth::attempt can succeed
            if (empty($existing->password)) {
                $existing->password = Hash::make($plain);
                $existing->save();
            }
        }

        // 1) Primary path: users table (students, teachers, *pure* admins)
        if (Auth::attempt(['email' => $email, 'password' => $plain, 'role' => $roleWant], $remember)) {
            $request->session()->regenerate();
            return $this->redirectByRole($roleWant, $go)
                ->with('ok', 'Welcome, ' . (Auth::user()->name ?? ''));
        }

        // 2) Legacy teachers fallback — *teacher role only*
        //
        // This is the important change for "pure admin" support:
        //  - We no longer log in as admin via the teachers table.
        //  - Admin accounts must exist in users.role = 'admin'.
        if ($roleWant === 'teacher') {
            $teacher = Teacher::where('email', $email)->first();

            // Verify against legacy teacher table hash
            if ($teacher && $teacher->password && Hash::check($plain, $teacher->password)) {
                // Ensure a users row exists AND has a password
                $user = User::where('email', $teacher->email)->first();

                if ($user && !empty($user->role) && $user->role !== $roleWant) {
                    return back()
                        ->withErrors([
                            'email' => "This account is '{$user->role}'. You selected '{$roleWant}'.",
                        ])
                        ->withInput($request->only('email'));
                }

                // Create or repair the users row (ALWAYS set password)
                if (!$user) {
                    $user = User::create([
                        'email'    => $teacher->email,
                        'name'     => $teacher->name ?? 'Teacher',
                        'role'     => 'teacher',          // force teacher role here
                        'password' => Hash::make($plain),
                    ]);
                } else {
                    $dirty = false;

                    if (empty($user->role)) {
                        $user->role = 'teacher';
                        $dirty = true;
                    }
                    if (empty($user->password)) {
                        $user->password = Hash::make($plain);
                        $dirty = true;
                    }
                    if ($dirty) {
                        $user->save();
                    }
                }

                // Log in via users guard
                Auth::login($user, $remember);
                $request->session()->regenerate();

                return $this->redirectByRole('teacher', $go)
                    ->with('ok', 'Welcome, ' . ($teacher->name ?? ''));
            }
        }

        // No luck
        return back()
            ->withErrors(['email' => 'Invalid credentials.'])
            ->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to login and also mark it no-store (so the next GET /login is fresh)
        return $this->noStore(
            redirect()->route('login')->with('ok', 'Logged out successfully.')
        );
    }

    private function redirectByRole(string $role, ?string $go)
    {
        if ($go === 'student' || $role === 'student') {
            return redirect()->intended(route('student.dashboard'));
        }
        if ($role === 'admin') {
            return redirect()->intended(route('admin.dashboard'));
        }
        // teacher
        return redirect()->intended(route('students.index'));
    }
}