@extends('layout')

@section('body')
<h2>New Student</h2>

<form method="post" action="{{ route('students.store') }}">
  @csrf

  <label>First name</label>
  <input name="first_name" value="{{ old('first_name') }}" required>

  <label>Last name</label>
  <input name="last_name" value="{{ old('last_name') }}" required>

  <label>Email</label>
  <input name="email" type="email" value="{{ old('email') }}">

  <label>Class</label>
  <input name="class" value="{{ old('class') }}">

  <label>Year</label>
  <input name="year" type="number" min="1" max="13" value="{{ old('year') }}">

  <button class="btn">Save</button>
  <a class="btn" href="{{ route('students.index') }}">Cancel</a>
</form>
@endsection