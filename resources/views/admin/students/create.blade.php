@extends('layout')

@section('body')
  <h2>Add Student</h2>

  @if ($errors->any())
    <div class="flash" style="background:#fee; border:1px solid #f99;">
      <ul style="margin:0; padding-left:18px;">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.students.store') }}" style="max-width:900px;">
    @csrf

    <label>Full name</label>
    <input type="text" name="name" value="{{ old('name') }}" required />

    <label>Email (username)</label>
    <input type="email" name="email" value="{{ old('email') }}" required />

    {{-- ✅ Parent contact fields --}}
    <label>Parent Name</label>
    <input type="text" name="parent_name" value="{{ old('parent_name') }}" placeholder="e.g. Fatima Al Zahra" />

    <label>Parent Email</label>
    <input type="email" name="parent_email" value="{{ old('parent_email') }}" placeholder="e.g. parent@example.com" />

    <label>Parent Phone</label>
    <input type="text" name="parent_phone" value="{{ old('parent_phone') }}" placeholder="+971 50 123 4567" />

    <div style="display:grid; grid-template-columns: 1fr auto; gap:12px; align-items:center;">
      <div>
        <label>Set initial password (optional)</label>
        <input type="text" name="password"
               placeholder="Leave blank to auto-generate"
               value="{{ old('password') }}" />
      </div>

      <div style="margin-top:22px;">
        <label style="display:flex; gap:8px; align-items:center;">
          <input type="checkbox" name="generate_password" value="1"
                 {{ old('generate_password') ? 'checked' : '' }}>
          <span>Generate strong password</span>
        </label>
      </div>
    </div>

    <label>Assign to teacher (optional)</label>
    {{-- IMPORTANT: name="assign_to" must match controller --}}
    <select name="assign_to">
      <option value="">— Unassigned —</option>
      @foreach($teachers as $t)
        <option value="{{ $t->id }}" {{ (string) old('assign_to') === (string) $t->id ? 'selected' : '' }}>
          {{ $t->name }}
        </option>
      @endforeach
    </select>

    <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn" type="submit">Create</button>
      <a class="btn" href="{{ route('admin.students.index') }}">Cancel</a>
    </div>
  </form>
@endsection