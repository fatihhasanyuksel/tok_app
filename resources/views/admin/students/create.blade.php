@extends('layout')

@section('body')
  <style>
    /* Scoped styling – only affects student create/edit pages */
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

    /* Flash + errors */
    .admin-student-page .flash {
      margin-bottom: 16px;
      padding: 10px 12px;
      border-radius: 8px;
      background: #ecfeff;
      border: 1px solid #7dd3fc;
      font-size: 14px;
      color: #0f172a;
    }
    .admin-student-page .flash--error {
      background: #fef2f2;
      border-color: #fecaca;
    }

    /* Form layout */
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
      max-width: 100%;
      box-sizing: border-box;
      padding: 8px 10px;
      border-radius: 8px;
      border: 1px solid #d1d5db;
      font-size: 14px;
      font-family: inherit;
    }
    .admin-student-form input:focus,
    .admin-student-form select:focus {
      outline: 2px solid #0b6bd6;
      outline-offset: 1px;
      border-color: #0b6bd6;
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
      gap: 10px;
      flex-wrap: wrap;
    }

    /* NEW pill button */
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
    .generate-pill-btn:hover {
      background: #e0e7ff;
    }
    .generate-pill-btn.done {
      background: #dcfce7 !important;
      border-color: #86efac !important;
      color: #166534 !important;
    }
  </style>

  <div class="admin-student-page">
    <h2>Add Student</h2>
    <p class="admin-student-subtitle">
      Create a new student account, add parent contact details, and (optionally) assign a ToK teacher.
    </p>

    @if ($errors->any())
      <div class="flash flash--error">
        <ul style="margin:0; padding-left:18px;">
          @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('admin.students.store') }}">
      @csrf

      <div class="admin-student-form">
        <div>
          <label>Full name</label>
          <input type="text" name="name" value="{{ old('name') }}" required />
        </div>

        <div>
          <label>Email (username)</label>
          <input type="email" name="email" value="{{ old('email') }}" required />
        </div>

        {{-- Parent contact fields --}}
        <div>
          <label>Parent Name</label>
          <input
            type="text"
            name="parent_name"
            value="{{ old('parent_name') }}"
            placeholder="e.g. Fatima Al Zahra"
          />
        </div>

        <div>
          <label>Parent Email</label>
          <input
            type="email"
            name="parent_email"
            value="{{ old('parent_email') }}"
            placeholder="e.g. parent@example.com"
          />
        </div>

        <div>
          <label>Parent Phone</label>
          <input
            type="text"
            name="parent_phone"
            value="{{ old('parent_phone') }}"
            placeholder="+971 50 123 4567"
          />
        </div>

        <div class="admin-student-inline-row">
          <div>
            <label>Set initial password (optional)</label>
            <input
              type="text"
              name="password"
              placeholder="Leave blank to auto-generate"
              value="{{ old('password') }}"
            />
          </div>

          {{-- NEW pill button replacing checkbox --}}
          <div>
            <button type="button" id="generate-pass-btn" class="generate-pill-btn">
              Generate strong password
            </button>
          </div>
        </div>

        <div>
          <label>Assign to teacher (optional)</label>
          <select name="assign_to">
            <option value="">— Unassigned —</option>
            @foreach($teachers as $t)
              <option value="{{ $t->id }}" {{ (string) old('assign_to') === (string) $t->id ? 'selected' : '' }}>
                {{ $t->name }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="admin-student-actions">
        <button class="btn" type="submit">Create</button>
          <button
    type="button"
    class="btn"
    onclick="window.location='{{ route('admin.students.index') }}'"
  >
    Cancel
  </button>
      </div>
    </form>
  </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('generate-pass-btn');
  const pw  = document.querySelector('input[name="password"]');

  if (!btn || !pw) return;

  function generateEasyStrongPassword() {
    const upper   = "ABCDEFGHJKLMNPQRSTUVWXYZ";     // No I, O
    const lower   = "abcdefghijkmnopqrstuvwxyz";     // No l
    const digits  = "23456789";                     // No 0,1
    const symbols = "!@#$%";

    const all = upper + lower + digits + symbols;

    // Ensure one from each group
    let pass = "";
    pass += upper[Math.floor(Math.random() * upper.length)];
    pass += lower[Math.floor(Math.random() * lower.length)];
    pass += digits[Math.floor(Math.random() * digits.length)];
    pass += symbols[Math.floor(Math.random() * symbols.length)];

    // Fill to 8 chars
    for (let i = 0; i < 4; i++) {
      pass += all[Math.floor(Math.random() * all.length)];
    }

    // Shuffle
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