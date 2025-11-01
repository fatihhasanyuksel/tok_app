@extends('layout')

@section('body')
<h2 style="margin-top:0">Login</h2>

@if ($errors->any())
  <div class="flash" style="background:#ffecec;border:1px solid #f5c2c2;">
    {{ $errors->first() }}
  </div>
@endif

<form method="POST" action="{{ route('login.attempt') }}" novalidate>
  @csrf

  <label>Email</label>
  <input type="email" name="email" value="{{ old('email') }}" required>

  <label>Password</label>
  <input type="password" name="password" required>

  <label>Role</label>
  <select name="role" required>
    <option value="">-- Select Role --</option>
    <option value="student" {{ old('role')==='student'?'selected':'' }}>Student</option>
    <option value="teacher" {{ old('role')==='teacher'?'selected':'' }}>Teacher</option>
    <option value="admin" {{ old('role')==='admin'?'selected':'' }}>Admin</option>
  </select>

  <button type="submit" class="btn" style="margin-top:10px;">Login</button>
</form>
@endsection