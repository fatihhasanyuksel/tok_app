@extends('layout')

@php use Illuminate\Support\Str; @endphp

@section('body')
  <h2 style="margin-top:0">Manage Students</h2>

  {{-- flash messages (scoped keys preferred, old keys as fallback) --}}
  @php
      // Prefer scoped keys to avoid duplicates with layout-level flashes
      $ok  = session('ok_students') ?? session()->pull('ok');
      $gen = session('generated_password_students') ?? session()->pull('generated_password');
  @endphp

  @if($ok)
    <div class="flash">{{ $ok }}</div>
  @endif

  @if($gen)
    <div class="flash">
      Generated password:
      <code>{{ $gen }}</code>
      — copy it now; it will not be shown again.
    </div>
  @endif

  <nav style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
    <a class="btn" href="{{ route('admin.students.create') }}">Add Student</a>
    <a class="btn" href="{{ route('admin.dashboard') }}">Back to Admin</a>
  </nav>

  {{-- search --}}
  <form method="get" action="{{ route('admin.students.index') }}" style="margin:12px 0;display:flex;gap:8px;align-items:center">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search by email or ID…" />
    <button class="btn">Search</button>
    @if($q !== '')
      <a class="btn" href="{{ route('admin.students.index') }}">Clear</a>
    @endif
  </form>

  @if($students->isEmpty())
    <p class="small muted">No students found.</p>
  @else
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

            // Fallback to a 'name' field if present (e.g., when rows came from users)
            if ($studentName === '' && !empty($s->name)) {
                $studentName = $s->name;
            }

            // Final fallback: use email prefix prettified
            if ($studentName === '') {
                $studentName = isset($s->email) ? Str::of($s->email)->before('@')->headline() : '—';
            }

            // Resolve teacher name from provided $teachers list
            $t = $teachers->firstWhere('id', $s->teacher_id);
            $teacherName = $t->name ?? '—';

            // Always use scalar id for routes (works for stdClass or model)
            $sid = is_object($s) ? ($s->id ?? null) : ($s['id'] ?? null);
          @endphp
          <tr>
            <td>{{ $studentName }}</td>
            <td>{{ $s->email }}</td>
            <td>{{ $teacherName }}</td>
            <td>
              <a class="btn" href="{{ route('admin.students.edit', ['student' => $sid]) }}">Edit</a>

              <form method="POST"
                    action="{{ route('admin.students.destroy', ['student' => $sid]) }}"
                    style="display:inline"
                    onsubmit="return confirm('Delete this student? This does not remove their user account.');">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit">Delete</button>
              </form>

              <form method="POST"
                    action="{{ route('admin.students.reset', ['student' => $sid]) }}"
                    style="display:inline"
                    onsubmit="return confirm('Generate a new password for this student?');">
                @csrf
                <input type="hidden" name="generate" value="1">
                <button class="btn" type="submit">Reset PW</button>
              </form>
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