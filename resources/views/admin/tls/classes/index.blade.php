@extends('layout')

@section('head')
  {{-- Re-use the same admin CSS as the main dashboard --}}
  <link rel="stylesheet" href="{{ asset('tok-admin/css/tok-admin-dashboard.css') }}">
@endsection

@section('content')
  <div class="tok-admin-shell">

    <h2 class="admin-page-title">ToK Learning Space – Classes</h2>
    <p class="admin-page-subtitle">
      View all ToK Learning Space classes across teachers. (Read-only for now.)
    </p>

    <p style="margin-bottom: 12px;">
      <a href="{{ route('admin.dashboard') }}" class="btn">
        ← Back to Admin Dashboard
      </a>
    </p>

    <div class="card">
      <div class="card-header card-header-muted">
        <div class="card-header-title">
          All Classes ({{ $classes->count() }})
        </div>
      </div>

      <div class="card-body">
        @if ($classes->isEmpty())
          <p class="empty-state-text">No classes found in ToK Learning Space yet.</p>
        @else
          <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
              <thead>
                <tr>
                  <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Class</th>
                  <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Teacher</th>
                  <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Students</th>
                  <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Status</th>
                  <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Created</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($classes as $class)
                  <tr class="tls-class-row">
                    <td style="padding:8px; border-bottom:1px solid #f3f4f6;">
                      <a href="{{ route('tok-ls.admin.classes.show', $class->id) }}">
                        {{ $class->name }}
                      </a>
                    </td>
                    <td style="padding:8px; border-bottom:1px solid #f3f4f6;">
                      {{ $teacherNames[$class->teacher_id] ?? '—' }}
                    </td>
                    <td style="padding:8px; border-bottom:1px solid #f3f4f6;">
                      {{ $class->students_count }}
                    </td>
                    <td style="padding:8px; border-bottom:1px solid #f3f4f6;">
                      @if ($class->archived_at)
                        <span style="display:inline-block; padding:2px 8px; border-radius:999px; background:#fee2e2; color:#b91c1c;">
                          Archived
                        </span>
                      @else
                        <span style="display:inline-block; padding:2px 8px; border-radius:999px; background:#dcfce7; color:#166534;">
                          Active
                        </span>
                      @endif
                    </td>
                    <td style="padding:8px; border-bottom:1px solid #f3f4f6; white-space:nowrap;">
                      {{ optional($class->created_at)->format('Y-m-d') ?? '—' }}
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>

  </div>
@endsection