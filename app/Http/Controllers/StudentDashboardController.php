<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentDashboardController extends Controller
{
    public function index(Request $request)
    {
        // If not logged in, send to teacher login but tag the intent so post-login
        // TeacherAuthController can send them back to /student.
        if (!Auth::check()) {
            return redirect()->route('login', ['go' => 'student']);
        }

        $user = Auth::user();

        return view('students.dashboard', [
            'student' => $user,
        ]);
    }
}