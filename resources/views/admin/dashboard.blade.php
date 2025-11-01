@extends('layout')

@section('body')
  <h2 style="margin-top:0">Admin Dashboard</h2>

  {{-- 🔹 Flash removed (handled by layout globally) --}}

  <nav style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
    <a class="btn" href="{{ route('admin.transfer') }}">Transfer Students</a>
    <a class="btn" href="{{ route('admin.teachers.index') }}">Manage Teachers</a>
    <a class="btn" href="{{ route('resources.manage') }}">Manage Resources</a>
    <a class="btn" href="{{ route('admin.students.index') }}">Manage Students</a>
  </nav>

  <p class="small muted">
    You are logged in as <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }}).
  </p>
@endsection