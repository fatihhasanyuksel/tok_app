@extends('layout')

@section('head')
    {{-- Load unified admin button + table styling --}}
    <link rel="stylesheet" href="{{ asset('tok-admin/css/tok-admin-dashboard.css') }}">
@endsection

@section('body')
  <style>
    /* Same scoped styles as create page */
    .admin-student-page {
      max-width: 960px;
      margin: 32px auto;
      padding: 24px 28px 28px;
      background: #ffffff;
      border-radius: 12px;
      border: 1px solid #e5e7eb;
      box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }
    .admin-student-page h2 {
      margin-top: 0;
      margin-bottom: 16px;
      font-size: 22px;
      font-weight: 600;
      color: #111827;
    }
    .admin-student-subtitle {
      margin: 0 0 20px;
      font-size: 14px;
      color: #6b7280;
    }

    .admin-student-page .flash {
      margin-bottom: 16px;
      padding: 10px 12px;
      border-radius: 8px;
      background: #ecfeff;
      border: 1px solid #7dd3fc;
      font-size: 14px;
      color: #0f172a;
    }
    .admin-student-page .flash code {
      background: #0b1120;
      color: #e5e7eb;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 13px;
    }
    .admin-student-page .flash--error {
      background: #fef2f2;
      border-color: #fecaca;
    }

    .admin-student-form {
      display: grid;
      gap: 12px;
    }
    .admin-student-form label {
      font-size: 14px;
      font-weight: 500;
      color: #374151;
      margin-bottom: 2px;
      display: block;
    }
    .admin-student-form input,
    .admin-student-form select {
      width: 100%;
      padding: 8px 10px;
      border-radius: 8px;
      border: 1px solid #d1d5db;
      font-size: 14px;
      font-family: inherit;
    }

    .admin-student-inline-row {
      display: grid;
      grid-template-columns: minmax(0, 2fr) minmax(0, 1.2fr);
      gap: 16px;
      align-items: end;
    }

    .admin-student-actions {
      margin-top: 20px;
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      align-items: center;
    }

    /* Password generator pill */
    .generate-pill-btn {
      padding: 8px 16px;
      border-radius: 999px;
      background: #eef2ff;
      border: 1px solid #c7d2fe;
      font-size: 14px;
      color: #4338ca;
      cursor: pointer;
      transition: 0.2s;
      width: 100%;
      text-align: center;
      user-select: none;
    }
    .generate-pill-btn:hover { background: #e0e7ff; }
    .generate-pill-btn.done {
      background: #dcfce7 !important;
      border-color: #86efac !important;
      color: #166534 !important;
    }

    /* stronger hover for delete */
    .btn-danger:hover {
      background: #dc2626;
      border-color: #b91c1c;
      color: #ffffff;
    }
  </style>

  <div class="admin-student-page">
    <h2>Edit Student</h2>
    <p class="admin-student-subtitle">
      Update student details, parent contacts, password, or teacher assignment.
    </p>

    {{-- Scoped flash messages --}}
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

    {{-- Validation --}}
    @if ($errors->any())
      <div class="flash flash--error">
        <ul style="margin:0; padding-left:18px;">
          @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('admin.students.update', $student) }}">
      @csrf
      @method('PUT')

      <div class="admin-student-form">
        <div>
          <label>Full name</label>
          <input type="text"
                 name="name"
                 value="{{ old('name', trim($student->first_name.' '.$student->last_name)) }}"
                 required>
        </div>

        <div>
          <label>Email (username)</label>
          <input type="email"
                 name="email"
                 value="{{ old('email', $student->email) }}"
                 required>
        </div>

        <div>
          <label>Parent Name</label>
          <input type="text"
                 name="parent_name"
                 value="{{ old('parent_name', $student->parent_name) }}">
        </div>

        <div>
          <label>Parent Email</label>
          <input type="email"
                 name="parent_email"
                 value="{{ old('parent_email', $student->parent_email) }}">
        </div>

        <div>
          <label>Parent Phone</label>
          <input type="text"
                 name="parent_phone"
                 value="{{ old('parent_phone', $student->parent_phone) }}">
        </div>

        <div class="admin-student-inline-row">
          <div>
            <label>Set new password (optional)</label>
            <input type="text"
                   name="password"
                   id="edit-password-field"
                   placeholder="Leave blank to keep current"
                   value="{{ old('password') }}">
          </div>

          <div>
            <button type="button"
                    id="generate-pass-btn"
                    class="generate-pill-btn">
              Generate strong password
            </button>
          </div>
        </div>

        <div>
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
        </div>
      </div>

      <div class="admin-student-actions">

        {{-- Save --}}
        <button class="workspace-link-btn" type="submit">
          Save Changes
        </button>

        {{-- Cancel --}}
        <button type="button"
                class="workspace-link-btn"
                onclick="window.location='{{ route('admin.students.index') }}'">
          Cancel
        </button>

        {{-- Reset Password --}}
        <form method="POST"
              action="{{ route('admin.students.reset', $student) }}"
              style="display:inline">
          @csrf
          <input type="hidden" name="generate" value="1">
          <button class="workspace-link-btn" type="submit">
            Reset Password
          </button>
        </form>

        {{-- Delete (unchanged) --}}
        <form method="POST"
              action="{{ route('admin.students.destroy', $student) }}"
              style="display:inline"
              onsubmit="return confirm('Delete this student? This does not remove their user account.');">
          @csrf
          @method('DELETE')
          <button class="btn btn-danger" type="submit">
            Delete
          </button>
        </form>

      </div>
    </form>
  </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('generate-pass-btn');
  const pw  = document.querySelector('input[name="password"]');

  if (!btn || !pw) return;

  function generateEasyStrongPassword() {
    const upper   = "ABCDEFGHJKLMNPQRSTUVWXYZ";
    const lower   = "abcdefghijkmnopqrstuvwxyz";
    const digits  = "23456789";
    const symbols = "!@#$%";
    const all = upper + lower + digits + symbols;

    let pass = "";
    pass += upper[Math.floor(Math.random()*upper.length)];
    pass += lower[Math.floor(Math.random()*lower.length)];
    pass += digits[Math.floor(Math.random()*digits.length)];
    pass += symbols[Math.floor(Math.random()*symbols.length)];

    for (let i = 0; i < 4; i++)
      pass += all[Math.floor(Math.random()*all.length)];

    return pass.split("").sort(() => 0.5 - Math.random()).join("");
  }

  btn.addEventListener('click', () => {
    const strong = generateEasyStrongPassword();
    pw.value = strong;
    pw.focus();
    pw.select();

    btn.classList.add("done");
    btn.textContent = "Password generated!";

    setTimeout(() => {
      btn.classList.remove("done");
      btn.textContent = "Generate strong password";
    }, 2000);
  });
});
</script>
@endsection