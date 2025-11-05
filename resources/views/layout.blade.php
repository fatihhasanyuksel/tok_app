<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>{{ config('app.name','ToK App') }}</title>

  {{-- CSRF for all forms & JS --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <style>
  /* Global layout */
  body{
    font-family: system-ui, Segoe UI, Roboto, Ubuntu, sans-serif;
    /* Use most of the viewport, max 1400px for readability */
    max-width: min(1400px, 96vw);
    margin: 40px auto;
    padding: 0 20px;
  }
  @media (min-width: 1600px){
    body{ max-width: 1500px; }
  }

  /* --- Top bar: title on left, actions on right --- */
  header.top-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
    margin-bottom:14px;
  }
  header.top-bar h1{
    margin:0;
  }
  .top-actions{
    display:flex;
    align-items:center;
    gap:14px;
    flex-wrap:wrap;
  }
  .top-actions .muted{
    color:#666;
    font-size:14px;
  }
  .btn-link{
    background:none;border:none;padding:0;margin:0;
    color:#6b21a8; /* purple-ish to match your links */
    text-decoration:underline; cursor:pointer; font:inherit;
  }
  .btn-link:hover{ text-decoration:none; }

  /* Keep your existing button look for other places */
  .btn.btn-danger{ background:#eee; border:1px solid #ddd; padding:.35rem .6rem; border-radius:8px; }
  form.inline{ display:inline; }
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
  <link rel="stylesheet" href="{{ asset('css/overrides.css') }}">
</head>
<body>
@php
  $user          = auth()->user();
  $legacyTeacher = request()->attributes->get('teacher');
  $displayName   = $user->name ?? ($legacyTeacher->name ?? 'User');
  $role          = $user->role ?? null;
@endphp

<header class="top-bar">
  <h1>ToK App</h1>

  {{-- Right-aligned actions --}}
  <div class="top-actions">
    @if($user)
      <span class="muted">Logged in as: <strong>{{ $displayName }}</strong></span>

      {{-- Role-aware quick links (keep Admin/Students shortcuts) --}}
      @if($role === 'admin')
        @unless (request()->routeIs('admin.dashboard'))
          <a class="btn-link" href="{{ route('admin.dashboard') }}">Admin</a>
        @endunless
      @elseif($role === 'teacher')
        @unless (request()->routeIs('students.index'))
          <a class="btn-link" href="{{ route('students.index') }}">Students</a>
        @endunless
      @elseif($role === 'student')
        @unless (request()->routeIs('student.dashboard'))
          <a class="btn-link" href="{{ route('student.dashboard') }}">Dashboard</a>
        @endunless
      @endif

      {{-- Resources for ALL roles --}}
      <a class="btn-link" href="{{ route('resources.index') }}">Resources</a>

      {{-- Logout --}}
      <form method="POST" action="{{ route('logout') }}" class="inline">
        @csrf
        <button type="submit" class="btn-link">Logout</button>
      </form>
@else
  @if (!View::hasSection('hide_login_link'))
    <a class="btn-link" href="{{ route('login') }}">Login</a>
  @endif
@endif

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

{{-- Messages panel (hidden unless you later add a toggle) --}}
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

<script>
(function () {
  const panel = document.getElementById('msg-panel');
  const toggle = document.getElementById('msg-toggle'); // may be absent
  const closeBt = document.getElementById('msg-close');
  const list = document.getElementById('msg-list');
  const form = document.getElementById('msg-form');

  if (!toggle || !panel) return;

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