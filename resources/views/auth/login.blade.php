@extends('layout')

{{-- Hide redundant top-right "Login" link --}}
@section('hide_login_link', true)

@section('body')
<style>
  /* ===== Layout and card ===== */
  .login-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 80vh;
    background: #f9f9f9;
  }

  .login-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    padding: 32px;
    width: 100%;
    max-width: 560px;          /* roomy but controlled */
  }

  /* Prevent any child from overflowing the card */
  .login-card, .login-card * { box-sizing: border-box; }

  .login-card h2 {
    text-align: center;
    margin-bottom: 24px;
  }

  /* ===== Inputs ===== */
  .form-group { margin-bottom: 16px; position: relative; }
  label { display:block; margin-bottom: 6px; font-weight: 600; }

  input, select {
    width: 100%;
    padding: 10px 40px 10px 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
    outline: none;
    transition: border 0.2s ease, box-shadow 0.2s ease;
  }
  input:focus, select:focus {
    border-color: #1a73e8;
    box-shadow: 0 0 0 1px #1a73e8;
  }

  /* ===== Password field with SHOW/HIDE pill ===== */
  .pw-field .pw-box { position: relative; }
  .pw-field .pw-box input { padding-right: 84px; }

  .pw-toggle-text {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    display: none;
    padding: 6px 10px;
    font-size: 13px;
    line-height: 1;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: #fff;
    color: #374151;
    cursor: pointer;
  }
  .pw-toggle-text:hover { border-color: #b6bec8; }
  .pw-toggle-text.is-visible { display: inline-block; }

  /* ===== Button ===== */
  .btn-login {
    width: 100%;
    background: #1a73e8; /* Google blue */
    color: #fff;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
  }
  .btn-login:hover { background: #1669c1; }

  .flash-error {
    background: #ffecec;
    border: 1px solid #f5c2c2;
    padding: 10px 12px;
    border-radius: 6px;
    margin-bottom: 12px;
    font-size: 14px;
    color: #b91c1c;
  }
</style>

<div class="login-wrapper">
  <div class="login-card">
    <h2>Login</h2>

    @if ($errors->any())
      <div class="flash-error">
        {{ $errors->first() }}
      </div>
    @endif

    <form method="POST" action="{{ route('login.attempt') }}" novalidate>
      @csrf

      {{-- Email --}}
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required>
      </div>

      {{-- Password + SHOW/HIDE pill --}}
      <div class="form-group pw-field">
        <label>Password</label>
        <div class="pw-box">
          <input type="password" name="password" id="password" autocomplete="current-password" required>

          <button type="button"
                  id="pwToggle"
                  class="pw-toggle-text"
                  aria-label="Show password"
                  aria-pressed="false">Show</button>
        </div>
      </div>

      {{-- Role --}}
      <div class="form-group">
        <label>Role</label>
        <select name="role" required>
          <option value="">-- Select Role --</option>
          <option value="student" {{ old('role')==='student'?'selected':'' }}>Student</option>
          <option value="teacher" {{ old('role')==='teacher'?'selected':'' }}>Teacher</option>
          <option value="admin" {{ old('role')==='admin'?'selected':'' }}>Admin</option>
        </select>
      </div>

      {{-- Submit --}}
      <button type="submit" class="btn-login">Login</button>
    </form>
  </div>
</div>

<script>
  (function () {
    const input  = document.getElementById('password');
    const toggle = document.getElementById('pwToggle');

    function refreshVisibility() {
      const hasText = input.value.length > 0;
      toggle.classList.toggle('is-visible', hasText);
    }

    function togglePw() {
      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      toggle.textContent = showing ? 'Show' : 'Hide';
      toggle.setAttribute('aria-pressed', (!showing).toString());
      toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
      input.focus({ preventScroll: true });
    }

    input.addEventListener('input', refreshVisibility);
    input.addEventListener('focus', refreshVisibility);
    input.addEventListener('blur', refreshVisibility);
    toggle.addEventListener('click', togglePw);

    // initial state (handles autofill)
    refreshVisibility();
  })();
</script>
@endsection