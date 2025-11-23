<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserController extends Controller
{
    /**
     * List all pure admin accounts (users.role = admin).
     */
    public function index()
    {
        $admins = User::where('role', 'admin')
            ->orderBy('name')
            ->get();

        return view('admin.admins.index', compact('admins'));
    }

    /**
     * Show "Add New Admin Account" form.
     */
    public function create()
    {
        return view('admin.admins.create');
    }

    /**
     * Store a new admin user.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            'name'     => $data['name'],
            'email'    => strtolower($data['email']),
            'role'     => 'admin',
            'password' => Hash::make($data['password']),
        ]);

        return redirect()
            ->route('admin.admins.index')
            ->with('ok', 'Admin account created successfully.');
    }

    /**
     * Edit an existing admin user.
     */
    public function edit(User $admin)
    {
        // Ensure we only edit admins
        abort_unless($admin->role === 'admin', 404);

        return view('admin.admins.edit', compact('admin'));
    }

    /**
     * Update an existing admin user (name/email, optional password).
     */
    public function update(Request $request, User $admin)
    {
        abort_unless($admin->role === 'admin', 404);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email,' . $admin->id],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $admin->name  = $data['name'];
        $admin->email = strtolower($data['email']);

        if (!empty($data['password'])) {
            $admin->password = Hash::make($data['password']);
        }

        $admin->save();

        return redirect()
            ->route('admin.admins.index')
            ->with('ok', 'Admin account updated.');
    }

    /**
     * Delete an admin — but never delete the last one.
     */
public function destroy(User $admin)
{
    abort_unless($admin->role === 'admin', 404);

    $adminCount = User::where('role', 'admin')->count();

    if ($adminCount <= 1) {
        // Always go back to the Manage Admins page with a clear message
        return redirect()
            ->route('admin.admins.index')
            ->with('error', 'You cannot delete the last admin account.');
    }

    $admin->delete();

    return redirect()
        ->route('admin.admins.index')
        ->with('ok', 'Admin account deleted.');
}

    /**
     * Reset password for an admin.
     */
    public function resetPassword(User $admin)
    {
        abort_unless($admin->role === 'admin', 404);

        $newPassword = 'Password123!'; // temporary default
        $admin->password = Hash::make($newPassword);
        $admin->save();

        return back()->with(
            'ok',
            "Password reset for {$admin->name}. New temporary password: {$newPassword}"
        );
    }
}