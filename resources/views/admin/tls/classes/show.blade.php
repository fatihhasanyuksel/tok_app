@extends('layout')

@section('head')
  <link rel="stylesheet" href="{{ asset('tok-admin/css/tok-admin-dashboard.css') }}">
@endsection

@section('content')
  @php
    $currentUrl      = url()->current();
    $selectedId      = request()->query('student');
    $hasStudents     = $class->students && $class->students->count() > 0;
  @endphp

  <div class="tok-admin-shell">

    <p style="margin-bottom: 12px;">
      <a href="{{ route('tok-ls.admin.classes.index') }}" class="btn">
        ← Back to Classes
      </a>
    </p>

    {{-- Class header --}}
    <div class="card" style="margin-bottom: 16px;">
      <div class="card-header">
        <div class="card-header-title">
          Class: {{ $class->name }}
        </div>
      </div>
      <div class="card-body">
        <p><strong>Teacher:</strong> {{ optional($class->teacher)->name ?? '—' }}</p>

        <p>
          <strong>Status:</strong>
          @if ($class->archived_at)
            <span style="display:inline-block;padding:2px 8px;border-radius:999px;background:#fee2e2;color:#b91c1c;">
              Archived (since {{ optional($class->archived_at)->format('Y-m-d') ?? 'unknown' }})
            </span>
          @else
            <span style="display:inline-block;padding:2px 8px;border-radius:999px;background:#dcfce7;color:#166534;">
              Active
            </span>
          @endif
        </p>

        <p><strong>Created:</strong> {{ optional($class->created_at)->format('Y-m-d H:i') ?? '—' }}</p>
      </div>
    </div>

    {{-- Two-column --}}
    <div style="display:flex; gap:16px; align-items:stretch;">

      {{-- LEFT: Students --}}
      <div class="card" style="flex:0 0 280px;">
        <div class="card-header">
          <div class="card-header-title">
            Students ({{ $class->students->count() }})
          </div>
        </div>

        <div class="card-body" style="padding:0;">
          @if (!$hasStudents)
            <p class="empty-state-text" style="padding:12px 16px;">
              No students assigned to this class.
            </p>
          @else
            <ul style="list-style:none;margin:0;padding:0;">
              @foreach ($class->students as $student)
                @php
                  $isActive = (string)$selectedId === (string)$student->id;
                  $href     = $currentUrl . '?student=' . $student->id;
                @endphp

                <li>
                  <a
                    href="{{ $href }}"
                    class="tls-student-item {{ $isActive ? 'is-active' : '' }}"
                    style="
                      display:block;
                      padding:10px 14px;
                      text-decoration:none;
                      border-bottom:1px solid #f3f4f6;
                      font-size:14px;
                      color:#111827;
                    "
                  >
                    {{ $student->name }}
                  </a>
                </li>

              @endforeach
            </ul>
          @endif
        </div>
      </div>

      {{-- RIGHT: TLS Student Metrics --}}
      <div class="card" style="flex:1 1 auto;">
        <div class="card-header">
          <div class="card-header-title">
            @if ($selectedStudent)
              Student metrics — {{ $selectedStudent->name }}
            @else
              Student metrics
            @endif
          </div>
        </div>

        <div class="card-body">
          @if (!$selectedStudent || !$studentMetrics)
            <p class="empty-state-text">Select a student on the left to view their TLS metrics.</p>
          @else

            <div style="display:flex; flex-direction:column; gap:8px; font-size:14px;">
              <div>
                <div style="font-size:11px;text-transform:uppercase;color:#6b7280;letter-spacing:.06em;">
                  Lessons assigned (TLS)
                </div>
                <div style="font-size:16px;font-weight:600;">
                  {{ $studentMetrics['lessons_assigned'] ?? 0 }}
                </div>
              </div>

              <div>
                <div style="font-size:11px;text-transform:uppercase;color:#6b7280;letter-spacing:.06em;">
                  Lessons opened
                </div>
                <div style="font-size:16px;font-weight:600;">
                  {{ $studentMetrics['lessons_opened'] ?? 0 }}
                </div>
              </div>

              <div>
                <div style="font-size:11px;text-transform:uppercase;color:#6b7280;letter-spacing:.06em;">
                  Responses submitted
                </div>
                <div style="font-size:16px;font-weight:600;">
                  {{ $studentMetrics['responses_submitted'] ?? 0 }}
                </div>
              </div>

              <div>
                <div style="font-size:11px;text-transform:uppercase;color:#6b7280;letter-spacing:.06em;">
                  Last TLS activity
                </div>
                <div style="font-size:14px;">
                  {{ $studentMetrics['last_activity'] ?? '—' }}
                </div>
              </div>
            </div>

            <div style="margin-top:16px;">
              <a href="{{ route('tok-ls.admin.classes.student-lessons', [$class->id, $selectedStudent->id]) }}"
                 style="
                    display:inline-block;
                    padding:6px 14px;
                    border-radius:999px;
                    background:#1d4ed8;
                    color:#ffffff;
                    text-decoration:none;
                    font-weight:600;
                    font-size:13px;
                 ">
                View this student's lessons (read-only)
              </a>
            </div>

          @endif
        </div>
      </div>

    </div>

  </div>
@endsection