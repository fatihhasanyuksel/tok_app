<?php

namespace App\Services;

use App\Models\Teacher;
use App\Models\Student;
use App\Models\Thread;
use Illuminate\Support\Facades\Schema;

class TeacherMetrics
{
    /**
     * Build global teacher/student metrics for the admin dashboard.
     *
     * You picked: 1, 4, 5, 6, 7, 8, 11, 12
     *
     *  1) totalTeachers
     *  4) teachersWithoutStudents
     *  5) totalStudents
     *  6) avgStudentsPerTeacher
     *  7) studentsPerTeacherBuckets (distribution)
     *  8) unassignedStudents
     * 11) threadsPerTeacher
     * 12) unresolvedThreadsPerTeacher
     */
    public function getGlobalMetrics(): array
    {
        // --- Teacher & student counts ---------------------------------------

        $totalTeachers = Teacher::count();
        $totalStudents = Student::count();

        // Teacher IDs that actually have at least one student
        $teacherIdsWithStudents = Student::whereNotNull('teacher_id')
            ->distinct()
            ->pluck('teacher_id')
            ->filter()
            ->values();

        $teachersWithStudents    = $teacherIdsWithStudents->count();
        $teachersWithoutStudents = max(0, $totalTeachers - $teachersWithStudents);

        // --- Students per teacher -------------------------------------------

        $studentsPerTeacher = Student::selectRaw('teacher_id, COUNT(*) as count')
            ->whereNotNull('teacher_id')
            ->groupBy('teacher_id')
            ->pluck('count', 'teacher_id'); // [teacher_id => count]

        $unassignedStudents = Student::whereNull('teacher_id')->count();

        $avgStudentsPerTeacher = $studentsPerTeacher->count() > 0
            ? round($studentsPerTeacher->avg(), 1)
            : 0.0;

        // Bucket distribution: 1–5, 6–10, 11–20, 21+
        $studentsPerTeacherBuckets = [
            '1-5'   => 0,
            '6-10'  => 0,
            '11-20' => 0,
            '21+'   => 0,
        ];

        foreach ($studentsPerTeacher as $count) {
            if ($count <= 5) {
                $studentsPerTeacherBuckets['1-5']++;
            } elseif ($count <= 10) {
                $studentsPerTeacherBuckets['6-10']++;
            } elseif ($count <= 20) {
                $studentsPerTeacherBuckets['11-20']++;
            } else {
                $studentsPerTeacherBuckets['21+']++;
            }
        }

        // --- Thread metrics (11 & 12) --------------------------------------
        //
        // Defensive: if the threads table or columns don’t exist
        // we just return empty arrays instead of crashing.

        $threadsPerTeacher           = [];
        $unresolvedThreadsPerTeacher = [];

        if (Schema::hasTable('threads')) {
            // 11) total threads per teacher (if threads.teacher_id exists)
            if (Schema::hasColumn('threads', 'teacher_id')) {
                $threadsPerTeacher = Thread::selectRaw('teacher_id, COUNT(*) as count')
                    ->groupBy('teacher_id')
                    ->pluck('count', 'teacher_id')
                    ->toArray();
            }

            // 12) unresolved threads per teacher (if is_resolved & teacher_id exist)
            if (
                Schema::hasColumn('threads', 'teacher_id') &&
                Schema::hasColumn('threads', 'is_resolved')
            ) {
                $unresolvedThreadsPerTeacher = Thread::selectRaw('teacher_id, COUNT(*) as count')
                    ->where('is_resolved', false)
                    ->groupBy('teacher_id')
                    ->pluck('count', 'teacher_id')
                    ->toArray();
            }
        }

        // --- Per-teacher breakdown ------------------------------------------
        //
        // For each teacher: students, total threads, unresolved threads.

        $teacherStats = Teacher::orderBy('name')
            ->get()
            ->map(function (Teacher $t) use ($studentsPerTeacher, $threadsPerTeacher, $unresolvedThreadsPerTeacher) {
                $id = $t->id;

                return [
                    'id'                       => $id,
                    'name'                     => $t->name,
                    'student_count'            => (int) ($studentsPerTeacher[$id] ?? 0),
                    'thread_count'             => (int) ($threadsPerTeacher[$id] ?? 0),
                    'unresolved_thread_count'  => (int) ($unresolvedThreadsPerTeacher[$id] ?? 0),
                ];
            })
            ->values()
            ->toArray();

        return [
            'totalTeachers'               => $totalTeachers,
            'teachersWithoutStudents'     => $teachersWithoutStudents,
            'totalStudents'               => $totalStudents,
            'avgStudentsPerTeacher'       => $avgStudentsPerTeacher,
            'studentsPerTeacherBuckets'   => $studentsPerTeacherBuckets,
            'unassignedStudents'          => $unassignedStudents,
            'threadsPerTeacher'           => $threadsPerTeacher,
            'unresolvedThreadsPerTeacher' => $unresolvedThreadsPerTeacher,
            'teacherStats'                => $teacherStats,
        ];
    }
}