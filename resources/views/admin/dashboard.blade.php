@extends('layout')

@section('head')
  <link rel="stylesheet" href="{{ asset('tok-admin/css/tok-admin-dashboard.css') }}">
@endsection

@section('body')
  <h2 class="admin-page-title">Admin Dashboard</h2>

  {{-- Flash removed (handled by layout globally) --}}

  <nav class="admin-nav">
    <a class="btn" href="{{ route('admin.transfer') }}">Transfer Students</a>
    <a class="btn" href="{{ route('admin.teachers.index') }}">Manage Teachers</a>
    <a class="btn" href="{{ route('admin.admins.index') }}">Manage Admins</a>
    <a class="btn" href="{{ route('resources.manage') }}">Manage Resources</a>
    <a class="btn" href="{{ route('admin.students.index') }}">Manage Students</a>
  </nav>

  <p class="admin-login-info">
    You are logged in as <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }}).
  </p>

  <hr class="admin-divider">

  <div class="tok-admin-shell">
    @php
        $teacherMetrics  = $teacherMetrics ?? [];
        $studentMetrics  = $studentMetrics ?? [];
        $selectedUserId  = $selectedUserId ?? null;

        $totalTeachers           = $teacherMetrics['totalTeachers'] ?? 0;
        $teachersWithoutStudents = $teacherMetrics['teachersWithoutStudents'] ?? 0;
        $totalStudents           = $teacherMetrics['totalStudents'] ?? 0;
        $unassignedStudents      = $teacherMetrics['unassignedStudents'] ?? 0;
        $avgStudentsPerTeacher   = $teacherMetrics['avgStudentsPerTeacher'] ?? 0;
        $buckets                 = $teacherMetrics['studentsPerTeacherBuckets'] ?? [];
        $teacherStats            = $teacherMetrics['teacherStats'] ?? [];
        $threadsPerTeacher       = $teacherMetrics['threadsPerTeacher'] ?? [];
        $unresolvedPerTeacher    = $teacherMetrics['unresolvedThreadsPerTeacher'] ?? [];

        $totalThreads     = is_array($threadsPerTeacher) ? array_sum($threadsPerTeacher) : 0;
        $totalUnresolved  = is_array($unresolvedPerTeacher) ? array_sum($unresolvedPerTeacher) : 0;
    @endphp

    {{-- GLOBAL: teacher insights --}}
    <div class="tok-admin-row">
      <div class="tok-admin-col">
        <div class="card">
          <div class="card-header">
            <h3>Teacher insights</h3>
          </div>
          <div class="card-body">
            <div class="overview-grid">

              {{-- Total teachers --}}
              <div class="overview-col">
                <div class="overview-label">Teachers</div>
                <div class="overview-value">{{ $totalTeachers }}</div>
                <div class="overview-sub">
                  {{ $teachersWithoutStudents }} without assigned students
                </div>
              </div>

              {{-- Total students --}}
              <div class="overview-col">
                <div class="overview-label">Students</div>
                <div class="overview-value">{{ $totalStudents }}</div>
                <div class="overview-sub">
                  {{ $unassignedStudents }} not assigned to any teacher
                </div>
              </div>

              {{-- All feedback threads --}}
              <div class="overview-col">
                <div class="overview-label">All feedback threads</div>
                <div class="overview-value">{{ $totalThreads }}</div>
                <div class="overview-sub">&nbsp;</div>
              </div>

              {{-- All resolved threads (calculated as total - unresolved) --}}
              <div class="overview-col">
                <div class="overview-label">All resolved threads</div>
                <div class="overview-value">{{ max($totalThreads - $totalUnresolved, 0) }}</div>
                <div class="overview-sub">Threads marked as resolved</div>
              </div>

              {{-- All unresolved threads --}}
              <div class="overview-col">
                <div class="overview-label">All unresolved threads</div>
                <div class="overview-value">{{ $totalUnresolved }}</div>
                <div class="overview-sub">Still awaiting action</div>
              </div>

            </div>

            @if(!empty($buckets))
              <div class="overview-buckets">
                <div class="overview-buckets-label">
                  Students per teacher distribution
                </div>
                <div class="overview-badges">
                  @foreach($buckets as $range => $count)
                    <span class="badge-pill">{{ $range }}: {{ $count }}</span>
                  @endforeach
                </div>
              </div>
            @endif

            @if(!empty($teacherStats))
              <hr class="overview-divider">

              {{-- Per-teacher metrics: list of teachers + metrics boxes --}}
              <div class="teacher-metrics-layout">
                <div class="teacher-list">
                  <div class="teacher-list-title">Teachers</div>
                  <ul>
                    @foreach($teacherStats as $idx => $row)
                      <li>
                        <button type="button"
                                class="teacher-pill"
                                data-teacher-index="{{ $idx }}">
                          {{ $row['name'] }}
                        </button>
                      </li>
                    @endforeach
                  </ul>
                </div>

                <div class="teacher-metrics-panel" id="teacher-metrics-panel">
                  <p class="metrics-empty">
                    Select a teacher on the left to view their metrics.
                  </p>
                </div>
              </div>
            @endif

          </div>
        </div>
      </div>
    </div>

    {{-- STUDENT INSIGHTS – blue bar card --}}
    <div class="tok-admin-row">
      <div class="tok-admin-col">
        <div class="card">
          <div class="card-header">
            <h3>Student insights</h3>
          </div>
          <div class="card-body">
            <p class="dash-sub" style="margin:0;">
              Search to view an individual student’s progress and activity.
            </p>
          </div>
        </div>
      </div>
    </div>

    {{-- Student selector --}}
    <div class="tok-admin-row">
      <div class="tok-admin-col">
        <div class="card">
          <div class="card-header card-header-muted">
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

  <script>
    const teacherStats = @json($teacherStats);

    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('student-search');
        const resultsDiv  = document.getElementById('student-results');
        const hiddenField = document.getElementById('student-id-hidden');

        if (searchInput && resultsDiv && hiddenField) {
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
        }

        // --- Teacher metrics panel (boxes) ---
        const metricsPanel   = document.getElementById('teacher-metrics-panel');
        const teacherButtons = document.querySelectorAll('.teacher-pill');

        function renderTeacherMetrics(index) {
            if (!metricsPanel || !teacherStats || !teacherStats.length) return;
            const row = teacherStats[index];
            if (!row) return;

            const totalThreads = row.thread_count || 0;
            const unresolved   = row.unresolved_thread_count || 0;
            const resolved     =
              (row.resolved_thread_count !== undefined && row.resolved_thread_count !== null)
                ? row.resolved_thread_count
                : Math.max(totalThreads - unresolved, 0);

            const neverReplied = row.never_replied ? 'Yes' : 'No';
            const avgReply     = row.avg_reply_hours
              ? Number(row.avg_reply_hours).toFixed(1) + ' h'
              : '—';
            const lastReply    = row.last_reply_at || '—';

            metricsPanel.innerHTML = `
              <h4 style="margin:0 0 8px;">${row.name}</h4>
              <div class="metrics-box-grid">
                <div class="metric-box">
                  <div class="metric-label">Students</div>
                  <div class="metric-value">${row.student_count || 0}</div>
                </div>
                <div class="metric-box">
                  <div class="metric-label">All threads</div>
                  <div class="metric-value">${totalThreads}</div>
                </div>
                <div class="metric-box">
                  <div class="metric-label">Resolved threads</div>
                  <div class="metric-value">${resolved}</div>
                </div>
                <div class="metric-box">
                  <div class="metric-label">Unresolved threads</div>
                  <div class="metric-value">${unresolved}</div>
                </div>
                <div class="metric-box">
                  <div class="metric-label">Threads never replied</div>
                  <div class="metric-value">${neverReplied}</div>
                </div>
                <div class="metric-box">
                  <div class="metric-label">Avg. response time</div>
                  <div class="metric-value">${avgReply}</div>
                </div>
                <div class="metric-box">
                  <div class="metric-label">Last reply</div>
                  <div class="metric-value">${lastReply}</div>
                </div>
              </div>
            `;
        }

        teacherButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                teacherButtons.forEach(b => b.classList.remove('is-active'));
                btn.classList.add('is-active');

                const idx = parseInt(btn.dataset.teacherIndex, 10);
                if (!Number.isNaN(idx)) {
                    renderTeacherMetrics(idx);
                }
            });
        });
    });
  </script>
@endsection