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

  <hr style="margin: 24px 0; border-color:#e5e7eb;">

  <div class="tok-admin-shell">
    {{-- Section title --}}
    <div class="tok-admin-row">
      <div class="tok-admin-col">
        <h2 class="dash-title">Student insights</h2>
        <p class="dash-sub">
          Search to view an individual student’s progress and activity.
        </p>
      </div>
    </div>

    {{-- Student selector --}}
    <div class="tok-admin-row">
      <div class="tok-admin-col">
        <div class="card">
          <div class="card-header">
            <h3>Find a student</h3>
          </div>

          <div class="card-body">
            <form method="GET" action="{{ route('admin.dashboard') }}">
              <div class="selector-grid">

                {{-- Search field with autocomplete --}}
                <div class="selector-field" style="position:relative;">
                  <label for="student-search">Search by name</label>

                  <input
                    type="search"
                    id="student-search"
                    placeholder="Start typing a student name…"
                    autocomplete="off"
                  >

                  {{-- Hidden field where selected student ID is stored --}}
                  <input type="hidden" name="student_id" id="student-id-hidden">

                  {{-- Autocomplete dropdown --}}
                  <div id="student-results" class="autocomplete-list"></div>
                </div>

                {{-- View button --}}
                <div class="selector-actions">
                  <button type="submit" id="student-view-btn">View student</button>
                </div>

              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    {{-- Student metrics section (shared partial) --}}
    <div class="tok-admin-row">
      <div class="tok-admin-col">
        @php
            // Make sure variables exist so partial never breaks
            $studentMetrics = $studentMetrics ?? [];
            $selectedUserId = $selectedUserId ?? null;
        @endphp

        @include('partials.student_metrics', [
            'selectedStudent' => $selectedStudent,
            'progress'        => $progress,
            'studentMetrics'  => $studentMetrics,
            'selectedUserId'  => $selectedUserId,
            'emptyMessage'    => 'Select a student above and click “View student” to see their Exhibition and Essay progress.',
        ])
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

    .card-header {
      padding: 12px 16px;
      border-bottom: 1px solid #e5e7eb;
      background: #0b6bd6;
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

    .selector-field input {
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

    /* Progress section – classes used by partial */
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

    /* Workspace link buttons – used inside partial if you kept them there */
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

    /* Autocomplete dropdown */
    .autocomplete-list {
      position: absolute;
      top: 60px;
      left: 0;
      right: 0;
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
      z-index: 20;
      max-height: 220px;
      overflow-y: auto;
      display: none;
    }

    .autocomplete-item {
      padding: 8px 10px;
      font-size: 14px;
      cursor: pointer;
    }

    .autocomplete-item:hover {
      background: #f3f4f6;
    }
  </style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('student-search');
    const resultsDiv  = document.getElementById('student-results');
    const hiddenField = document.getElementById('student-id-hidden');

    if (!searchInput || !resultsDiv || !hiddenField) return;

    // Build JS dataset from PHP
    const students = @json($students->map(fn($s) => [
        'id'   => $s->id,
        'name' => $s->name,
    ]));

    function showResults(list) {
        resultsDiv.innerHTML = '';
        if (!list.length) {
            resultsDiv.style.display = 'none';
            return;
        }

        list.forEach(st => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.textContent = st.name;
            item.dataset.id = st.id;

            item.addEventListener('click', () => {
                searchInput.value = st.name;
                hiddenField.value = st.id;
                resultsDiv.style.display = 'none';
            });

            resultsDiv.appendChild(item);
        });

        resultsDiv.style.display = 'block';
    }

    searchInput.addEventListener('input', () => {
        const q = searchInput.value.trim().toLowerCase();

        if (!q) {
            hiddenField.value = '';
            showResults([]);
            return;
        }

        const matches = students.filter(s =>
            s.name.toLowerCase().includes(q)
        );

        showResults(matches);
    });

    // Hide when clicking outside
    document.addEventListener('click', (e) => {
      if (!resultsDiv.contains(e.target) && e.target !== searchInput) {
          resultsDiv.style.display = 'none';
      }
    });
});
</script>
@endsection