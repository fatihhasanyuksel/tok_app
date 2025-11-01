@extends('layout')

@section('body')
<h2>Teachers</h2>

<p>
  <a class="btn" href="{{ route('admin.teachers.create') }}">Add Teacher</a>
</p>

@if(session('ok'))
  <div class="flash">{{ session('ok') }}</div>
@endif

<table>
  <thead>
    <tr>
      <th>Name</th>
      <th>Email</th>
      <th>Active</th>
      <th>Admin</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    @forelse($teachers as $t)
      <tr>
        <td>{{ $t->name }}</td>
        <td>{{ $t->email }}</td>
        <td>{{ $t->active ? 'Yes' : 'No' }}</td>
        <td>{{ $t->is_admin ? 'Yes' : 'No' }}</td>
        <td style="white-space:nowrap">
          <a class="btn" href="{{ route('admin.teachers.edit', $t) }}">Edit</a>
          <form method="post" action="{{ route('admin.teachers.destroy', $t) }}" style="display:inline">
            @csrf @method('DELETE')
            <button class="btn" onclick="return confirm('Delete this teacher?')">Delete</button>
          </form>
        </td>
      </tr>
    @empty
      <tr><td colspan="5">No teachers yet.</td></tr>
    @endforelse
  </tbody>
</table>

{{ $teachers->links() }}
@endsection