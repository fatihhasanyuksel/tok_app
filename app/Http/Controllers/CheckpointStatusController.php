<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckpointStatusController extends Controller
{
    public function update(Request $request)
    {
        // Validate payload from the dropdown
        $data = $request->validate([
            'student_id' => ['required','integer'],
            'work_type'  => ['required','string','in:exhibition,essay'],
            'stage_key'  => ['required','string','max:100'],
            'note'       => ['nullable','string','max:1000'],
        ]);

        $user = Auth::user();

        // --- Authorization Guard ---
        // Allow Admins always; restrict teachers to their own students.
        $isAdmin = in_array($user->role ?? '', ['admin','superadmin']);
        if (!$isAdmin) {
            $teacherId = DB::table('teachers')->where('email', $user->email)->value('id');
            if (!$teacherId) {
                return response()->json(['ok' => false, 'error' => 'Not a teacher.'], 403);
            }

            $ownsStudent = DB::table('students')
                ->where('id', $data['student_id'])
                ->where('teacher_id', $teacherId)
                ->exists();

            if (!$ownsStudent) {
                return response()->json(['ok' => false, 'error' => 'Forbidden (not your student).'], 403);
            }
        }
        // ----------------------------

        try {
            Log::info('checkpoints.status.update payload', [
                'student_id' => $data['student_id'],
                'work_type'  => $data['work_type'],
                'stage_key'  => $data['stage_key'],
                'note'       => $data['note'] ?? null,
                'user_id'    => $user->id,
            ]);

            // Preserve created_at on updates (two-step)
            $affected = DB::table('checkpoint_statuses')
                ->where('student_id', (int) $data['student_id'])
                ->where('type', (string) $data['work_type'])
                ->update([
                    'status_code' => (string) $data['stage_key'],
                    'selected_by' => $user->id,
                    'selected_at' => now(),
                    'note'        => $data['note'] ?? null,
                    'updated_at'  => now(),
                ]);

            if ($affected === 0) {
                DB::table('checkpoint_statuses')->insert([
                    'student_id'  => (int) $data['student_id'],
                    'type'        => (string) $data['work_type'],
                    'status_code' => (string) $data['stage_key'],
                    'selected_by' => $user->id,
                    'selected_at' => now(),
                    'note'        => $data['note'] ?? null,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::error('checkpoints.status.update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['ok' => false, 'error' => 'Server error'], 500);
        }
    }
}