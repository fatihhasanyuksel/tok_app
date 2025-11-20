{{-- resources/views/partials/student_metrics.blade.php --}}

@php
    // Allow dashboards to customize the "no student" message
    $emptyMessage = $emptyMessage ?? null;
@endphp

@if (! $selectedStudent)
    @if (!empty($emptyMessage))
        <p class="dash-sub">
            {{ $emptyMessage }}
        </p>
    @endif
@else
    {{-- Header: which student --}}
    <p style="margin:0 0 16px; font-size:14px;">
        Showing progress for <strong>{{ $selectedStudent->name }}</strong>.
    </p>

    {{-- Progress bars --}}
    <div class="progress-grid">
        {{-- Exhibition --}}
        <div class="progress-line">
            <div class="progress-label">
                <span>Exhibition</span>
                <span class="stage-pill">
                    {{ $progress['exhibition']['label'] ?? 'NO SUBMISSION' }}
                    ({{ $progress['exhibition']['percent'] ?? 0 }}%)
                </span>
            </div>
            <div class="progress-track">
                <div
                    class="progress-fill"
                    style="width: {{ $progress['exhibition']['percent'] ?? 0 }}%;"
                ></div>
            </div>
        </div>

        {{-- Essay --}}
        <div class="progress-line">
            <div class="progress-label">
                <span>Essay</span>
                <span class="stage-pill stage-pill-secondary">
                    {{ $progress['essay']['label'] ?? 'NO SUBMISSION' }}
                    ({{ $progress['essay']['percent'] ?? 0 }}%)
                </span>
            </div>
            <div class="progress-track">
                <div
                    class="progress-fill progress-fill-secondary"
                    style="width: {{ $progress['essay']['percent'] ?? 0 }}%;"
                ></div>
            </div>
        </div>
    </div>

{{-- Quick links into workspaces (optional) --}}
@if (!empty($selectedStudent) && !empty($selectedUserId) && ($showWorkspaces ?? true))
    <div class="workspace-links">
        <a
            href="{{ route('workspace.show', ['type' => 'exhibition']) }}?student={{ $selectedUserId }}"
            class="workspace-link-btn"
        >
            Open Exhibition workspace
        </a>

        <a
            href="{{ route('workspace.show', ['type' => 'essay']) }}?student={{ $selectedUserId }}"
            class="workspace-link-btn workspace-link-btn-secondary"
        >
            Open Essay workspace
        </a>
    </div>
@endif

    <p class="dash-sub" style="margin-top:16px;">
        Progress is based on the latest teacher-selected stage for each component
        (No submission → Drafts → Final → Approved).
    </p>

    {{-- Writing activity metrics table (optional) --}}
    @if (!empty($studentMetrics))
        @php
            $ex = $studentMetrics['exhibition'] ?? [];
            $es = $studentMetrics['essay'] ?? [];

            $formatDelta = function ($n) {
                $n = (int) $n;
                if ($n > 0) return '+' . number_format($n);
                if ($n < 0) return number_format($n); // already has "-"
                return '0';
            };
        @endphp

        <div style="margin-top:24px; border-top:1px solid #e5e7eb; padding-top:16px;">
            <h3 style="font-size:14px; font-weight:600; margin:0 0 4px;">Writing activity</h3>
            <p class="dash-sub" style="margin-bottom:12px;">
                Snapshot of current word count and recent writing activity for this student.
            </p>

            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:6px 0; border-bottom:1px solid #e5e7eb;">Component</th>
                        <th style="text-align:left; padding:6px 0; border-bottom:1px solid #e5e7eb;">Current word count</th>
                        <th style="text-align:left; padding:6px 0; border-bottom:1px solid #e5e7eb;">Words added (last 7 days)</th>
                        <th style="text-align:left; padding:6px 0; border-bottom:1px solid #e5e7eb;">Active days (last 30 days)</th>
                        <th style="text-align:left; padding:6px 0; border-bottom:1px solid #e5e7eb;">Last edit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding:6px 0;">Exhibition</td>
                        <td style="padding:6px 0;">
                            {{ number_format($ex['current_words'] ?? 0) }}
                        </td>
                        <td style="padding:6px 0;">
                            {{ $formatDelta($ex['words_added_7'] ?? 0) }}
                        </td>
                        <td style="padding:6px 0;">
                            {{ (int)($ex['active_days_30'] ?? 0) }}
                        </td>
                        <td style="padding:6px 0; color:#6b7280;">
                            {{ $ex['last_edit_human'] ?? '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;">Essay</td>
                        <td style="padding:6px 0;">
                            {{ number_format($es['current_words'] ?? 0) }}
                        </td>
                        <td style="padding:6px 0;">
                            {{ $formatDelta($es['words_added_7'] ?? 0) }}
                        </td>
                        <td style="padding:6px 0;">
                            {{ (int)($es['active_days_30'] ?? 0) }}
                        </td>
                        <td style="padding:6px 0; color:#6b7280;">
                            {{ $es['last_edit_human'] ?? '—' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
@endif