<?php

namespace App\Services;

use App\Models\Student;
use App\Models\CheckpointStatus;
use App\Models\Submission;
use App\Models\Version;
use Carbon\Carbon;

class StudentMetrics
{
    /**
     * Build metrics for a single student.
     *
     * Returned structure matches what your admin dashboard + partial expect:
     * - selectedStudent
     * - progress         (exhibition / essay stage)
     * - studentMetrics   (writing activity per component)
     * - selectedUserId   (we default from user_id; controller can override)
     */
    public function buildForStudent(?Student $student): array
    {
        if (! $student) {
            return [
                'selectedStudent' => null,
                'progress'        => $this->defaultProgress(),
                'studentMetrics'  => [],
                'selectedUserId'  => null,
            ];
        }

        return [
            'selectedStudent' => $student,
            'progress'        => $this->progressFor($student),
            'studentMetrics'  => $this->studentMetrics($student),
            'selectedUserId'  => $student->user_id ?? null,
        ];
    }

    /**
     * Default progress structure when no student is selected.
     */
    protected function defaultProgress(): array
    {
        return [
            'exhibition' => ['percent' => 0, 'label' => 'NO SUBMISSION', 'raw' => 'no_submission'],
            'essay'      => ['percent' => 0, 'label' => 'NO SUBMISSION', 'raw' => 'no_submission'],
        ];
    }

    /**
     * Map stage keys to labels and percentages.
     * This mirrors the map you had in the Blade.
     */
    protected function stageMap(): array
    {
        return [
            'no_submission' => ['label' => 'No submission', 'percent' => 0],
            'draft_1'       => ['label' => 'Draft 1',        'percent' => 25],
            'draft_2'       => ['label' => 'Draft 2',        'percent' => 50],
            'draft_3'       => ['label' => 'Draft 3',        'percent' => 75],
            'final'         => ['label' => 'Final',          'percent' => 90],
            'approved'      => ['label' => 'Approved',       'percent' => 100],
        ];
    }

    /**
     * Compute Exhibition + Essay progress for a given student.
     * This is the logic lifted out of the admin Blade.
     */
    protected function progressFor(Student $student): array
    {
        $stageMap = $this->stageMap();
        $progress = $this->defaultProgress();

        foreach (['exhibition', 'essay'] as $type) {
            $status = CheckpointStatus::where('student_id', $student->id)
                ->where('type', $type)              // 'exhibition' or 'essay'
                ->latest('selected_at')
                ->first();

            $key  = $status->status_code ?? 'no_submission';
            $meta = $stageMap[$key] ?? $stageMap['no_submission'];

            $progress[$type] = [
                'percent' => $meta['percent'],
                'label'   => strtoupper($meta['label']),
                'raw'     => $key,
            ];
        }

        return $progress;
    }

    /**
     * Per-student writing metrics bundle (Exhibition + Essay).
     *
     * This is the logic that was previously inside AdminController::dashboard().
     * We are now centralising it here so both Admin + Teacher dashboards
     * can share it.
     */
    protected function studentMetrics(Student $student): array
    {
        $selectedUserId = $student->user_id ?? null;

        if (! $selectedUserId) {
            return [];
        }

        $metricsByType = [];

        $now   = Carbon::now();
        $cut7  = $now->copy()->subDays(7);
        $cut30 = $now->copy()->subDays(30);

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
                    $metrics['current_words']   = $this->wordCountFromHtml($latestVersion->body_html);
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
                        $this->wordCountFromHtml($lastV->body_html) -
                        $this->wordCountFromHtml($firstV->body_html);
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

            $metricsByType[$type] = $metrics;
        }

        return $metricsByType;
    }

    /**
     * Helper: count words from HTML safely.
     * Same logic you originally had in the controller.
     */
    protected function wordCountFromHtml(?string $html): int
    {
        $plain = strip_tags((string) $html);
        $plain = html_entity_decode($plain, ENT_QUOTES, 'UTF-8');
        $plain = preg_replace('/\s+/u', ' ', trim($plain));

        if ($plain === '' || $plain === null) {
            return 0;
        }

        $parts = preg_split('/\s+/u', $plain);
        return $parts ? count($parts) : 0;
    }
}