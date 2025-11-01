<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Thread;

class ThreadPolicy
{
    // Students can view their own thread; teachers/admin can view all
    public function view(User $user, Thread $thread): bool
    {
        if (in_array($user->role, ['teacher', 'admin'], true)) return true;
        return $thread->student_id === $user->id;
    }

    // Students can update their own text; teachers/admin can update all
    public function update(User $user, Thread $thread): bool
    {
        if (in_array($user->role, ['teacher', 'admin'], true)) return true;
        return $thread->student_id === $user->id;
    }

    // ONLY teacher/admin may change workflow status
    public function changeStatus(User $user, Thread $thread): bool
    {
        return in_array($user->role, ['teacher', 'admin'], true);
    }

    // Notes restricted to teacher/admin
    public function addNote(User $user, Thread $thread): bool
    {
        return in_array($user->role, ['teacher', 'admin'], true);
    }

    // Deletion restricted to teacher/admin
    public function delete(User $user, Thread $thread): bool
    {
        return in_array($user->role, ['teacher', 'admin'], true);
    }
}