@extends('layout')

@section('body')
  {{-- Header row --}}
  <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin:0 0 8px;">
    <h2 style="margin:0;">Teachers</h2>
    <a class="btn" href="{{ route('admin.teachers.create') }}">Add Teacher</a>
  </div>

  {{-- Flash (unchanged logic) --}}
  @if(session('ok'))
    <div class="flash">{{ session('ok') }}</div>
  @endif

  {{-- Table styles (match Students page look) --}}
  <style>
    table { width:100%; border-collapse:collapse; margin-top:10px; }
    th, td { padding:10px 12px; border-bottom:1px solid #eee; text-align:left; font-size:14px; }
    th { font-weight:600; background:#fafafa; }
    tr:hover td { background:#f9f9f9; }
    .actions { display:flex; gap:6px; flex-wrap:wrap; }
    .btn-ghost { background:#f7f7f7; border:1px solid #e5e5e5; padding:6px 10px; border-radius:8px; font-size:13px; }
    .btn-danger-ghost { background:#fff5f5; border:1px solid #f3b4b4; color:#b62020; }
  </style>

  <table>
    <thead>
      <tr>
        <th style="width:30%">Name</th>
        <th style="width:35%">Email</th>
        <th style="width:10%">Active</th>
        <th style="width:10%">Admin</th>
        <th style="width:15%"></th>
      </tr>
    </thead>
    <tbody>
      @forelse($teachers as $t)
        <tr>
          <td>{{ $t->name }}</td>
          <td>{{ $t->email }}</td>
          <td>{{ $t->active ? 'Yes' : 'No' }}</td>
          <td>{{ $t->is_admin ? 'Yes' : 'No' }}</td>
          <td>
            <div class="actions">
              <a class="btn-ghost" href="{{ route('admin.teachers.edit', $t) }}">Edit</a>

              <form method="post" action="{{ route('admin.teachers.destroy', $t) }}"
                    onsubmit="return confirm('Delete this teacher?')" style="display:inline">
                @csrf @method('DELETE')
                <button class="btn-ghost btn-danger-ghost" type="submit">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5">No teachers yet.</td></tr>
      @endforelse
    </tbody>
  </table>

  {{ $teachers->links() }}
@endsection