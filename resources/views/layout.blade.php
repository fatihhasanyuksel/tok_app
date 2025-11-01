<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>{{ config('app.name','ToK App') }}</title>

  {{-- CSRF for all forms & JS --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <style>
    body{font-family:system-ui,Segoe UI,Roboto,Ubuntu,sans-serif;max-width:900px;margin:40px auto;padding:0 16px}
    header{position:relative;display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;gap:12px}
    nav{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    a{color:#b30000;text-decoration:none}
    .btn{display:inline-block;padding:8px 14px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer}
    .btn-danger{border-color:#f0c4c4}
    .flash{background:#f6ffed;border:1px solid #b7eb8f;padding:10px;border-radius:8px;margin-bottom:16px}
    input,textarea,select{width:100%;padding:10px;border:1px solid #ccc;border-radius:8px}
    form > *{margin:8px 0}
    table{width:100%;border-collapse:collapse}
    th,td{padding:8px;border-bottom:1px solid #eee}
    small.muted{color:#666}
  </style>

  <script>
    (function () {
      const meta = document.querySelector('meta[name="csrf-token"]');
      if (!meta) return;
      const token = meta.getAttribute('content');
      const origFetch = window.fetch;
      window.fetch = function(input, init = {}) {
        const method = (init && init.method ? init.method : 'GET').toUpperCase();
        if (['POST','PUT','PATCH','DELETE'].includes(method)) {
          init.headers = Object.assign({'X-CSRF-TOKEN': token, 'X-Requested-With':'XMLHttpRequest'}, init.headers || {});
        }
        return origFetch(input, init);
      };
    })();
  </script>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
  $user          = auth()->user();
  $legacyTeacher = request()->attributes->get('teacher');
  $displayName   = $user->name ?? ($legacyTeacher->name ?? 'User');
  $role          = $user->role ?? null;
@endphp

<header>
  <h1>ToK App</h1>

  <nav>
    @if($user)
      <small class="muted">Logged in as: {{ $displayName }}</small>

      {{-- Role-aware header --}}
      @if($role === 'admin')
        @unless (request()->routeIs('admin.dashboard'))
          <a class="btn" href="{{ route('admin.dashboard') }}">Admin</a>
        @endunless

      @elseif($role === 'teacher')
        @unless (request()->routeIs('students.index'))
          <a class="btn" href="{{ route('students.index') }}">Students</a>
        @endunless
        {{-- Workspace link removed --}}
        <a class="btn" href="{{ route('resources.index') }}">Resources</a>
        {{-- Messages toggle intentionally removed for teachers --}}

      @elseif($role === 'student')
        @unless (request()->routeIs('student.dashboard'))
          <a class="btn" href="{{ route('student.dashboard') }}">Dashboard</a>
        @endunless
        {{-- Workspace link removed --}}
        <a class="btn" href="{{ route('resources.index') }}">Resources</a>
        {{-- Messages toggle intentionally removed for students --}}
      @endif

      {{-- Logout --}}
      <form method="POST" action="{{ route('logout') }}" style="display:inline">
        @csrf
        <button type="submit" class="btn btn-danger">Logout</button>
      </form>
    @else
      <a class="btn" href="{{ route('login') }}">Login</a>
    @endif
  </nav>

  {{-- Messages panel --}}
  <div id="msg-panel" style="display:none; position:absolute; right:16px; top:72px; width:360px; max-height:60vh; overflow:auto; background:#fff; border:1px solid #e5e5e5; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.12); z-index:9999; padding:12px;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px;">
      <strong>Messages</strong>
      <button type="button" class="btn" id="msg-close" style="padding:4px 8px;">Close</button>
    </div>
    <div id="msg-list">
      <p class="small muted" style="margin:6px 0;">Loading…</p>
    </div>
    <form id="msg-form" style="margin-top:10px; display:none;">
      @csrf
      <textarea name="body" rows="3" placeholder="Type a message…" required
        style="width:100%; padding:8px; border:1px solid #ccc; border-radius:8px;"></textarea>
      <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px;">
        <button type="submit" class="btn">Send</button>
      </div>
    </form>
  </div>
</header>

@hasSection('suppress_global_flash')
@else
  @if(session('ok'))
    <div class="flash">{{ session('ok') }}</div>
  @endif
@endif

@hasSection('body')
  @yield('body')
@else
  @hasSection('content')
    @yield('content')
  @endif
@endif

<script>
(function () {
  const panel = document.getElementById('msg-panel');
  const toggle = document.getElementById('msg-toggle'); // may be absent
  const closeBt = document.getElementById('msg-close');
  const list = document.getElementById('msg-list');
  const form = document.getElementById('msg-form');

  // If no toggle (because header hides Messages), do nothing.
  if (!toggle || !panel) return;

  const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  async function fetchJSON(url) {
    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    return res.json();
  }

  function renderMessages(messages) {
    if (!messages?.length) {
      list.innerHTML = '<p class="small muted" style="margin:6px 0;">No messages yet.</p>';
      return;
    }
    const html = messages.map(m => {
      const when = m.created_at ? new Date(m.created_at).toLocaleString() : '';
      const body = (m.body || '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/\n/g,'<br>');
      return `<div class="msg" style="border:1px solid #eee;border-radius:8px;padding:8px;margin:8px 0;">
        <div style="color:#666;font-size:12px;margin-bottom:4px;">${when}</div>
        <div>${body}</div></div>`;
    }).join('');
    list.innerHTML = html;
  }

  toggle.addEventListener('click', (e) => {
    e.preventDefault();
    panel.style.display = (panel.style.display === 'none' || panel.style.display === '') ? 'block' : 'none';
  });
  closeBt.addEventListener('click', () => panel.style.display = 'none');
})();
</script>

</body>
</html>