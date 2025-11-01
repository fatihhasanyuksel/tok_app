@extends('layout')

@section('body')
<h2>Add Teacher</h2>

<form method="post" action="{{ route('admin.teachers.store') }}">
  @csrf

  <label>Name</label>
  <input name="name" value="{{ old('name') }}" required>

  <label>Email</label>
  <input type="email" name="email" value="{{ old('email') }}" required>

  <label>Password</label>
  <input type="password" name="password" required>

  <label><input type="checkbox" name="active" value="1" {{ old('active')?'checked':'' }}> Active</label>
  <label><input type="checkbox" name="is_admin" value="1" {{ old('is_admin')?'checked':'' }}> Admin</label>

  <button class="btn">Save</button>
  <a class="btn" href="{{ route('admin.teachers.index') }}">Cancel</a>
</form>
@endsection