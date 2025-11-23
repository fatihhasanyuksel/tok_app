@extends('layout')

@section('body')
<h2>Add Teacher</h2>

<style>
  .form {max-width:720px}
  .row{margin:14px 0}
  .label{display:block;font-weight:600;margin-bottom:6px}
  .field{width:100%;padding:10px;border:1px solid #ccc;border-radius:8px}
  .capsule{display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid #ddd;border-radius:10px;background:#fafafa;margin-right:10px}
  .actions{display:flex;gap:8px;margin-top:18px}
  .error{color:#b00020;font-size:12px;margin-top:6px}
  .hint{display:block;color:#666;font-size:12px;margin-top:4px}
</style>

<form class="form" method="POST" action="{{ route('admin.teachers.store') }}">
  @csrf

  <div class="row">
    <label class="label" for="name">Full name</label>
    <input id="name" name="name" class="field" value="{{ old('name') }}" required>
    @error('name') <div class="error">{{ $message }}</div> @enderror
  </div>

  <div class="row">
    <label class="label" for="email">Email</label>
    <input id="email" type="email" name="email" class="field" value="{{ old('email') }}" required>
    @error('email') <div class="error">{{ $message }}</div> @enderror
  </div>

  <div class="row">
    <label class="label" for="password">Password</label>
    <input id="password" type="password" name="password" class="field" placeholder="Min 8 characters" required>
    <small class="hint">Choose a temporary password; they can change it later.</small>
    @error('password') <div class="error">{{ $message }}</div> @enderror
  </div>

  <div class="row" aria-label="Status">
    {{-- Active --}}
    <span class="capsule">
      <input type="hidden" name="active" value="0">
      <input id="active" type="checkbox" name="active" value="1" {{ old('active') ? 'checked' : '' }}>
      <label for="active" style="margin:0;cursor:pointer;">
        <strong>Active</strong>
        <span class="hint">Can sign in</span>
      </label>
    </span>
  </div>

  <div class="actions">
    <button class="btn" type="submit">Save</button>
    <a class="btn btn-danger" href="{{ route('admin.teachers.index') }}">Cancel</a>
  </div>
</form>
@endsection