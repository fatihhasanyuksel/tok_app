<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\Version;
use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Admin landing dashboard (Phase 5)
     */
    public function dashboard(Request $request)
    {
        // Load students and their linked user accounts (if any)
        $students = Student::with('user')
            ->orderBy('id')
            ->get();

        $selectedStudent = null;
        $selectedUserId  = null;   // this will become the ?student= value
        $studentMetrics  = null;   // per-component metrics (exhibition / essay)

        if ($request->filled('student_id')) {
            $selectedStudent = Student::with('user')->find($request->student_id);

            if ($selectedStudent) {
                // 1) Prefer the explicit relationship
                if ($selectedStudent->user) {
                    $selectedUserId = (int) $selectedStudent->user->id;
                } elseif (!empty($selectedStudent->user_id)) {
                    // 2) Fallback if relation isn't loaded for some reason
                    $selectedUserId = (int) $selectedStudent->user_id;
                } else {
                    // 3) Last-resort fallback: resolve via email (very rare now)
                    $selectedUserId = User::where('email', $selectedStudent->email)->value('id');
                }
            }
        }

        // ---------- Student writing metrics (per component) ----------
        if ($selectedStudent && $selectedUserId) {
            // Helper: count words from HTML safely
            $wordCountFromHtml = function (?string $html): int {
                $plain = strip_tags((string) $html);
                $plain = html_entity_decode($plain, ENT_QUOTES, 'UTF-8');
                $plain = preg_replace('/\s+/u', ' ', trim($plain));

                if ($plain === '' || $plain === null) {
                    return 0;
                }

                $parts = preg_split('/\s+/u', $plain);
                return $parts ? count($parts) : 0;
            };

            $studentMetrics = [];
            $now    = Carbon::now();
            $cut7   = $now->copy()->subDays(7);
            $cut30  = $now->copy()->subDays(30);

            foreach (['exhibition', 'essay'] as $type) {
                $metrics = [
                    'current_words'   => 0,
                    'last_edit'       => null,
                    'last_edit_human' => null,
                    'words_added_7'   => 0,
                    'active_days_30'  => 0,
                ];

                $submission = Submission::where('student_id', $selectedUserId)
                    ->where('type', $type)
                    ->first();

                if ($submission) {
                    // Latest version → current word count + last edit
                    $latestVersion = $submission->latestVersion()->first();
                    if ($latestVersion) {
                        $metrics['current_words']   = $wordCountFromHtml($latestVersion->body_html);
                        $metrics['last_edit']       = $latestVersion->created_at;
                        $metrics['last_edit_human'] = optional($latestVersion->created_at)->diffForHumans();
                    }

                    // Words added in last 7 days
                    $versionsLast7 = Version::where('submission_id', $submission->id)
                        ->where('created_at', '>=', $cut7)
                        ->orderBy('created_at', 'asc')
                        ->get();

                    if ($versionsLast7->count() >= 1) {
                        $firstV = $versionsLast7->first();
                        $lastV  = $versionsLast7->last();

                        $metrics['words_added_7'] =
                            $wordCountFromHtml($lastV->body_html) -
                            $wordCountFromHtml($firstV->body_html);
                        // can be negative if the student has shortened the text
                    }

                    // Active days in last 30 days
                    $metrics['active_days_30'] = Version::where('submission_id', $submission->id)
                        ->where('created_at', '>=', $cut30)
                        ->pluck('created_at')
                        ->map(fn ($dt) => $dt->toDateString())
                        ->unique()
                        ->count();
                }

                $studentMetrics[$type] = $metrics;
            }
        }

        return view('admin.dashboard', [
            'students'        => $students,
            'selectedStudent' => $selectedStudent,
            'selectedUserId'  => $selectedUserId,
            'studentMetrics'  => $studentMetrics,
        ]);
    }

    /**
     * Show transfer form (move students from one teacher to another)
     */
    public function transferForm(Request $request)
    {
        $teachers = Teacher::orderBy('name')->get();
        $students = Student::orderBy('id')->get();

        return view('admin.transfer', compact('teachers', 'students'));
    }

    /**
     * Process transfer form submission
     */
    public function transferDo(Request $request)
    {
        $data = $request->validate([
            'to_teacher_id'   => ['required', 'integer', 'exists:teachers,id'],
            'student_ids'     => ['required', 'array', 'min:1'],
            'student_ids.*'   => ['integer', 'exists:students,id'],
        ]);

        $count = Student::whereIn('id', $data['student_ids'])
            ->update(['teacher_id' => $data['to_teacher_id']]);

        return redirect()
            ->route('admin.transfer')
            ->with('ok_transfer', "Transferred {$count} student(s).");
    }
}