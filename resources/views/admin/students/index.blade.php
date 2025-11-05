@extends('layout')

@php
    use Illuminate\Support\Str;
    // Safety: these may not be set depending on controller
    $q = $q ?? '';
    $teachers = $teachers ?? collect();
@endphp

@section('suppress_global_flash', true)

@section('body')
  {{-- Page title --}}
  <div style="margin:4px 0 8px;">
    <h2 style="margin:0;">Manage Students</h2>
  </div>

  {{-- local flashes (scoped keys preferred, old keys as fallback) --}}
  @php
      $ok  = session('ok_students') ?? session()->pull('ok');
      $gen = session('generated_password_students') ?? session()->pull('generated_password');
  @endphp
  @if($ok)
    <div class="flash" style="margin-top:6px">{{ $ok }}</div>
  @endif
  @if($gen)
    <div class="flash" style="margin-top:6px">
      Generated password:
      <code>{{ $gen }}</code>
      — copy it now; it will not be shown again.
    </div>
  @endif

  {{-- search --}}
  <form method="get" action="{{ route('admin.students.index') }}"
        style="margin:14px 0 8px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search by email or ID…"
           style="min-width:260px;" />
    <button class="btn">Search</button>
    @if($q !== '')
      <a class="btn" href="{{ route('admin.students.index') }}">Clear</a>
    @endif
  </form>

  {{-- Add Student (moved here, top-left above the table) --}}
  <div style="margin:10px 0;">
    <a class="btn" href="{{ route('admin.students.create') }}">Add Student</a>
  </div>

  @if($students->isEmpty())
    <p class="small muted" style="margin-top:10px;">No students found.</p>
  @else
    {{-- Match teacher list aesthetics --}}
    <style>
      table { width: 100%; border-collapse: collapse; margin-top: 10px; }
      th, td { padding: 10px 12px; border-bottom: 1px solid #eee; text-align: left; font-size: 14px; vertical-align: top; }
      th { font-weight: 600; background: #fafafa; }
      tr:hover td { background: #f9f9f9; }
      .t-name { font-weight: 600; }
      .muted { color: #777; font-size: 13px; }

      /* Nicer action buttons without changing your global .btn */
      .btn-ghost { background:#f7f7f7; border:1px solid #e5e5e5; padding:6px 10px; border-radius:8px; font-size:13px; }
      .btn-danger-ghost { background:#fff5f5; border:1px solid #f3b4b4; color:#b62020; }
      .actions-stack { display:flex; gap:6px; flex-wrap:wrap; }
      @media (min-width: 1024px) {
        .actions-stack { flex-wrap:nowrap; }
      }
    </style>

    <table>
      <thead>
        <tr>
          <th style="width:32%">Name</th>
          <th style="width:32%">Email</th>
          <th style="width:20%">Teacher</th>
          <th style="width:16%">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($students as $s)
          @php
            // Build a display name safely:
            $first = $s->first_name ?? '';
            $last  = $s->last_name  ?? '';
            $studentName = trim($first . ' ' . $last);

            if ($studentName === '' && !empty($s->name)) {
              $studentName = $s->name;
            }
            if ($studentName === '') {
              $studentName = isset($s->email) ? Str::of($s->email)->before('@')->headline() : '—';
            }

            $t = $teachers->firstWhere('id', $s->teacher_id);
            $teacherName = $t->name ?? '—';

            $sid = is_object($s) ? ($s->id ?? null) : ($s['id'] ?? null);
          @endphp

          <tr>
            <td class="t-name">{{ $studentName }}</td>
            <td>{{ $s->email }}</td>
            <td>{{ $teacherName }}</td>
            <td>
              <div class="actions-stack">
                <a class="btn-ghost" href="{{ route('admin.students.edit', ['student' => $sid]) }}">Edit</a>

                <form method="POST"
                      action="{{ route('admin.students.destroy', ['student' => $sid]) }}"
                      onsubmit="return confirm('Delete this student? This does not remove their user account.');">
                  @csrf @method('DELETE')
                  <button class="btn-ghost btn-danger-ghost" type="submit">Delete</button>
                </form>

                <form method="POST"
                      action="{{ route('admin.students.reset', ['student' => $sid]) }}"
                      onsubmit="return confirm('Generate a new password for this student?');">
                  @csrf
                  <input type="hidden" name="generate" value="1">
                  <button class="btn-ghost" type="submit">Reset PW</button>
                </form>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div style="margin-top:12px">
      {{ $students->links() }}
    </div>
  @endif
@endsection