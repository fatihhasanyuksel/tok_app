@extends('layout')

@section('body')
<h2>Edit Student</h2>

<form method="post" action="{{ route('students.update', $student) }}">
  @csrf
  @method('PUT')

  <label>First name</label>
  <input name="first_name" value="{{ old('first_name', $student->first_name) }}" required>

  <label>Last name</label>
  <input name="last_name" value="{{ old('last_name', $student->last_name) }}" required>

  <label>Student email</label>
  <input name="email" type="email" value="{{ old('email', $student->email) }}">

  <label>Parent email</label>
  <input name="parent_email" type="email" value="{{ old('parent_email', $student->parent_email) }}">

  <label>Parent phone</label>
  <input name="parent_phone" value="{{ old('parent_phone', $student->parent_phone) }}">

  <button class="btn">Save Changes</button>
  <a class="btn" href="{{ route('students.index') }}">Cancel</a>
</form>
@endsection