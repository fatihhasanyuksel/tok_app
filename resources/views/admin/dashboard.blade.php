@extends('layout')

@section('body')
  <h2 style="margin-top:0">Admin Dashboard</h2>

  {{-- Flash removed (handled by layout globally) --}}

  <nav style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
    <a class="btn" href="{{ route('admin.transfer') }}">Transfer Students</a>
    <a class="btn" href="{{ route('admin.teachers.index') }}">Manage Teachers</a>
    <a class="btn" href="{{ route('resources.manage') }}">Manage Resources</a>
    <a class="btn" href="{{ route('admin.students.index') }}">Manage Students</a>
  </nav>

  <p class="small muted">
    You are logged in as <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }}).
  </p>

@php
    use App\Models\CheckpointStatus;

    // Always have a collection
    $students = $students ?? collect();

    // Sort alphabetically by name (safe: if name missing, it just treats as null)
    $students = $students->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

    // Which student (if any) was chosen via ?student_id=...
    $selectedId = request()->integer('student_id');
    $selectedStudent = $selectedId
        ? $students->firstWhere('id', $selectedId)
        : null;

    // Map stage keys -> label + progress %
    $stageMap = [
        'no_submission' => ['label' => 'No submission', 'percent' => 0],
        'draft_1'       => ['label' => 'Draft 1',        'percent' => 25],
        'draft_2'       => ['label' => 'Draft 2',        'percent' => 50],
        'draft_3'       => ['label' => 'Draft 3',        'percent' => 75],
        'final'         => ['label' => 'Final',          'percent' => 90],
        'approved'      => ['label' => 'Approved',       'percent' => 100],
    ];

    // Default structure so blade doesn't explode if nothing is selected
    $progress = [
        'exhibition' => ['percent' => 0, 'label' => 'NO SUBMISSION', 'raw' => 'no_submission'],
        'essay'      => ['percent' => 0, 'label' => 'NO SUBMISSION', 'raw' => 'no_submission'],
    ];

    if ($selectedStudent) {
        foreach (['exhibition', 'essay'] as $type) {
            $status = CheckpointStatus::where('student_id', $selectedStudent->id)
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
    }
  @endphp

  <hr style="margin: 24px 0; border-color:#e5e7eb;">

  <div class="tok-admin-shell">
    {{-- Section title --}}
    <div class="tok-admin-row">
      <div class="tok-admin-col">
        <h2 class="dash-title">Student insights</h2>
        <p class="dash-sub">
          Search or browse to view an individual student’s progress and activity.
        </p>
      </div>
    </div>

    {{-- Student selector (single source of truth) --}}
    <div class="tok-admin-row">
      <div class="tok-admin-col">
        <div class="card">
          <div class="card-header">
            <h3>Find a student</h3>
          </div>

          <div class="card-body">
            <form method="GET" action="{{ route('admin.dashboard') }}">
              <div class="selector-grid">
                <div class="selector-field">
                  <label for="student-search">Search by name</label>
                  <input
                    type="search"
                    id="student-search"
                    placeholder="Start typing a student name…"
                  >
                </div>

                <div class="selector-field">
                  <label for="student-select">Browse alphabetically</label>
                  <select
                    id="student-select"
                    name="student_id"
                    data-selected-id="{{ $selectedId }}"
                  >
                    <option value="">Select a student…</option>
                    @foreach ($students as $student)
                      <option
                        value="{{ $student->id }}"
                        {{ $selectedId === $student->id ? 'selected' : '' }}
                      >
                        {{ $student->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="selector-actions">
                  <button type="submit" id="student-view-btn">View student</button>
                  <small class="selector-hint">
                    Select a student, then click “View student” to update the progress bars below.
                  </small>
                </div>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>

    {{-- Progress overview --}}
    <div class="tok-admin-row">
      <div class="tok-admin-col">
        <div class="card progress-card">
          <div class="card-header">
            <h3>Progress overview</h3>
          </div>

          <div class="card-body">
            @if (! $selectedStudent)
              <p class="dash-sub">
                Select a student above and click <strong>“View student”</strong> to see their Exhibition and Essay progress.
              </p>
            @else
              <p style="margin:0 0 16px; font-size:14px;">
                Showing progress for <strong>{{ $selectedStudent->name }}</strong>.
              </p>

              <div class="progress-grid">
                {{-- Exhibition --}}
                <div class="progress-line">
                  <div class="progress-label">
                    <span>Exhibition</span>
                    <span class="stage-pill">
                      {{ $progress['exhibition']['label'] }}
                      ({{ $progress['exhibition']['percent'] }}%)
                    </span>
                  </div>
                  <div class="progress-track">
                    <div
                      class="progress-fill"
                      style="width: {{ $progress['exhibition']['percent'] }}%;"
                    ></div>
                  </div>
                </div>

                {{-- Essay --}}
                <div class="progress-line">
                  <div class="progress-label">
                    <span>Essay</span>
                    <span class="stage-pill stage-pill-secondary">
                      {{ $progress['essay']['label'] }}
                      ({{ $progress['essay']['percent'] }}%)
                    </span>
                  </div>
                  <div class="progress-track">
                    <div
                      class="progress-fill progress-fill-secondary"
                      style="width: {{ $progress['essay']['percent'] }}%;"
                    ></div>
                  </div>
                </div>
              </div>

                            {{-- NEW: quick links into workspaces --}}
              @if ($selectedStudent && !empty($selectedUserId))
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
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  <style>
    .tok-admin-shell {
      max-width: 1100px;
      margin: 0 auto 40px;
      padding: 0 16px 40px;
      font-family: system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    }

    .tok-admin-row {
      display: flex;
      gap: 16px;
      margin-bottom: 16px;
    }

    .tok-admin-col {
      flex: 1 1 0;
    }

    .dash-title {
      margin: 0 0 4px;
      font-size: 20px;
      font-weight: 700;
    }

    .dash-sub {
      margin: 0;
      color: #6b7280;
      font-size: 14px;
    }

    .card {
      background: #ffffff;
      border-radius: 16px;
      border: 1px solid #e5e7eb;
      box-shadow: 0 10px 25px rgba(15,23,42,0.06);
      overflow: hidden;
    }

    .card-header {
      padding: 12px 16px;
      border-bottom: 1px solid #e5e7eb;
      background: linear-gradient(90deg,#0b6bd6,#22c55e);
      color: #ffffff;
    }

    .card-header-muted {
      background: #f9fafb;
      color: #111827;
    }

    .card-header h3 {
      margin: 0;
      font-size: 15px;
      font-weight: 600;
    }

    .card-body {
      padding: 16px;
    }

    .selector-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 12px 16px;
      align-items: flex-end;
    }

    .selector-field label {
      display: block;
      font-size: 13px;
      color: #4b5563;
      margin-bottom: 4px;
    }

    .selector-field input,
    .selector-field select {
      width: 100%;
      padding: 8px 10px;
      border-radius: 10px;
      border: 1px solid #d1d5db;
      font: inherit;
    }

    .selector-actions {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 4px;
    }

    #student-view-btn {
      padding: 8px 14px;
      border-radius: 999px;
      border: none;
      cursor: pointer;
      background: #0b6bd6;
      color: #ffffff;
      font-size: 14px;
      font-weight: 500;
    }

    #student-view-btn:hover {
      background: #0753aa;
    }

    .selector-hint {
      font-size: 11px;
      color: #9ca3af;
    }

    /* Progress section */
    .progress-grid {
      display: flex;
      flex-direction: column;
      gap: 14px;
      margin-top: 8px;
    }

    .progress-line {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .progress-label {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 13px;
      color: #374151;
    }

    .stage-pill {
      padding: 3px 8px;
      border-radius: 999px;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      background: rgba(59,130,246,0.1);
      color: #1d4ed8;
    }

    .stage-pill-secondary {
      background: rgba(16,185,129,0.1);
      color: #047857;
    }

    .progress-track {
      position: relative;
      width: 100%;
      height: 10px;
      border-radius: 999px;
      background: #f3f4f6;
      overflow: hidden;
    }

    .progress-fill {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 0%;
      border-radius: 999px;
      background: linear-gradient(90deg,#0b6bd6,#60a5fa);
      transition: width 0.25s ease-out;
    }

    .progress-fill-secondary {
      background: linear-gradient(90deg,#059669,#22c55e);
    }

    /* Workspace link buttons */
    .workspace-links {
      margin-top: 16px;
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .workspace-link-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 12px;
      border: 1px solid #0b6bd6;
      color: #0b6bd6;
      background: #eff6ff;
      text-decoration: none;
    }

    .workspace-link-btn:hover {
      background: #dbeafe;
    }

    .workspace-link-btn-secondary {
      border-color: #059669;
      color: #047857;
      background: #ecfdf5;
    }

    .workspace-link-btn-secondary:hover {
      background: #d1fae5;
    }

    @media (max-width: 640px) {
      .tok-admin-shell {
        padding-left: 8px;
        padding-right: 8px;
      }
    }
  </style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search   = document.getElementById('student-search');
    const dropdown = document.getElementById('student-select');

    if (!search || !dropdown) {
        console.warn('Student search wiring: elements not found');
        return;
    }

    const selectedId = String(dropdown.dataset.selectedId || '');

    // Take a snapshot of all options (including placeholder) BEFORE we mutate anything
    const allOptions = Array.from(dropdown.options).map(opt => ({
        value: opt.value,
        label: opt.textContent,
        isPlaceholder: !opt.value,
    }));

    function render(query) {
        const q = String(query || '').trim().toLowerCase();

        // Clear current options
        dropdown.innerHTML = '';

        // Always keep a placeholder at the top
        const placeholderDef =
            allOptions.find(o => o.isPlaceholder) ||
            { value: '', label: 'Select a student…', isPlaceholder: true };

        const placeholderOpt = document.createElement('option');
        placeholderOpt.value = placeholderDef.value;
        placeholderOpt.textContent = placeholderDef.label;
        dropdown.appendChild(placeholderOpt);

        // Add only matching students
        allOptions
            .filter(o => !o.isPlaceholder)
            .filter(o => !q || o.label.toLowerCase().includes(q))
            .forEach(o => {
                const opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.label;

                if (selectedId && o.value === selectedId) {
                    opt.selected = true;
                }

                dropdown.appendChild(opt);
            });
    }

    // Initial render (empty query)
    render(search.value);

    // Live filtering as the user types
    search.addEventListener('input', function () {
        render(search.value);
    });
});
</script>
@endsection