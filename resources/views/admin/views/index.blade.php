@extends('layout')

@section('content')
  <h2>Admin · Master Student List</h2>

  <p class="flash">Read-only skeleton for now. We’ll add Create / Edit / Assign / Reset Password next.</p>

  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>Student Email</th>
      </tr>
    </thead>
    <tbody>
      @forelse($students as $s)
        <tr>
          <td>{{ $s->last_name }}, {{ $s->first_name }}</td>
          <td>{{ $s->email ?? '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="2">No students yet.</td></tr>
      @endforelse
    </tbody>
  </table>

  <div style="margin-top:12px">
    {{ $students->links() }}
  </div>
@endsection