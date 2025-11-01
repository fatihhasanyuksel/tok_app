@extends('layout')

@section('body')
  <h2>Edit Student</h2>

  {{-- ✅ Step 2: Scoped flash messages --}}
  @if(session('ok_edit'))
    <div class="flash">{{ session('ok_edit') }}</div>
  @endif
  @if(session('generated_password_students'))
    <div class="flash">
      Generated password:
      <code>{{ session('generated_password_students') }}</code>
      — copy it now; it will not be shown again.
    </div>
  @endif

  {{-- Validation errors --}}
  @if ($errors->any())
    <div class="flash" style="background:#fee; border:1px solid #f99;">
      <ul style="margin:0; padding-left:18px;">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Main update form --}}
  <form method="POST" action="{{ route('admin.students.update', $student) }}" style="max-width:900px;">
    @csrf
    @method('PUT')

    <label>Full name</label>
    <input
      type="text"
      name="name"
      value="{{ old('name', trim($student->first_name.' '.$student->last_name)) }}"
      required
    />

    <label>Email (username)</label>
    <input
      type="email"
      name="email"
      value="{{ old('email', $student->email) }}"
      required
    />

    {{-- Parent contact fields --}}
    <label>Parent Name</label>
    <input
      type="text"
      name="parent_name"
      value="{{ old('parent_name', $student->parent_name) }}"
      placeholder="e.g. Fatima Al Zahra"
    />

    <label>Parent Email</label>
    <input
      type="email"
      name="parent_email"
      value="{{ old('parent_email', $student->parent_email) }}"
      placeholder="e.g. parent@example.com"
    />

    <label>Parent Phone</label>
    <input
      type="text"
      name="parent_phone"
      value="{{ old('parent_phone', $student->parent_phone) }}"
      placeholder="+971 50 123 4567"
    />

    <div style="display:grid; grid-template-columns: 1fr auto; gap:12px; align-items:center;">
      <div>
        <label>Set new password (optional)</label>
        <input
          type="text"
          name="password"
          placeholder="Leave blank to keep current"
          value="{{ old('password') }}"
        />
      </div>

      <div style="margin-top:22px;">
        <label style="display:flex; gap:8px; align-items:center;">
          <input
            type="checkbox"
            name="generate_password"
            value="1"
            {{ old('generate_password') ? 'checked' : '' }}
          >
          <span>Generate strong password</span>
        </label>
      </div>
    </div>

    <label>Assign to teacher (optional)</label>
    <select name="assign_to">
      <option value="">— Unassigned —</option>
      @foreach($teachers as $t)
        <option value="{{ $t->id }}"
          {{ (string) old('assign_to', $student->teacher_id) === (string) $t->id ? 'selected' : '' }}>
          {{ $t->name }}
        </option>
      @endforeach
    </select>

    <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn" type="submit">Save Changes</button>
      <a class="btn" href="{{ route('admin.students.index') }}">Cancel</a>
    </div>
  </form>

  {{-- Secondary actions: reset / delete --}}
  <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
    <form method="POST" action="{{ route('admin.students.reset', $student) }}" style="display:inline">
      @csrf
      <input type="hidden" name="generate" value="1">
      <button class="btn" type="submit">Reset Password</button>
    </form>

    <form method="POST" action="{{ route('admin.students.destroy', $student) }}" style="display:inline"
          onsubmit="return confirm('Delete this student? This does not remove their user account.');">
      @csrf
      @method('DELETE')
      <button class="btn btn-danger" type="submit">Delete</button>
    </form>
  </div>
@endsection