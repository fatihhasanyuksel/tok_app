@extends('layout')

@section('body')
<h2>Edit Teacher</h2>

<form method="post" action="{{ route('admin.teachers.update', $teacher) }}">
  @csrf @method('PUT')

  <label>Name</label>
  <input name="name" value="{{ old('name', $teacher->name) }}" required>

  <label>Email</label>
  <input type="email" name="email" value="{{ old('email', $teacher->email) }}" required>

  <label>New Password (leave blank to keep current)</label>
  <input type="password" name="password">

  <label>
    <input type="checkbox" name="active" value="1" {{ old('active', $teacher->active) ? 'checked' : '' }}>
    Active
  </label>

  <label>
    <input type="checkbox" name="is_admin" value="1" {{ old('is_admin', $teacher->is_admin) ? 'checked' : '' }}>
    Admin
  </label>

  <button class="btn">Update</button>
  <a class="btn" href="{{ route('admin.teachers.index') }}">Cancel</a>
</form>

<hr>

<h3>Reset Password</h3>
<form method="post" action="{{ route('admin.teachers.reset', $teacher) }}">
  @csrf
  <label>New Password</label>
  <input type="password" name="password" required>
  <button class="btn">Reset Password</button>
</form>
@endsection