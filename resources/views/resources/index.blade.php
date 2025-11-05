<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>ToK Resources</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;background:#fff;color:#111;}
    .top{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid #eee;}
    .wrap{max-width:900px;margin:0 auto;padding:18px;}
    .muted{color:#6b7280;}
    .grid{display:grid;grid-template-columns:1fr;gap:10px}
    @media(min-width:720px){ .grid{grid-template-columns:1fr 1fr} }
    .card{border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#fff;display:flex;flex-direction:column;gap:6px}
    .name{font-weight:600;word-break:break-word}
    .meta{font-size:12px;color:#6b7280}
    .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid #d0d7de;border-radius:8px;background:#f8fafc;color:#111;text-decoration:none}
    .row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
  </style>
</head>
<body>
  <div class="top">
    <strong>ToK Resources</strong>
    <div class="row">
      @auth
        @php $role = strtolower(auth()->user()->role ?? ''); @endphp

        {{-- Role-aware back link --}}
        @if($role === 'admin')
          <a class="btn" href="{{ route('admin.dashboard') }}">Admin dashboard</a>
        @elseif($role === 'teacher')
          <a class="btn" href="{{ route('students.index') }}">Students</a>
        @elseif($role === 'student')
          <a class="btn" href="{{ route('student.dashboard') }}">Dashboard</a>
        @endif

        {{-- Manage link for teacher/admin --}}
        @if(in_array($role, ['teacher','admin']))
          <a class="btn" href="{{ route('resources.manage') }}">Manage</a>
        @endif
      @endauth
    </div>
  </div>

  <div class="wrap">
    @if(session('ok'))   <div class="card" style="border-color:#b7eb8f;background:#e6ffed">{{ session('ok') }}</div> @endif
    @if(session('error'))<div class="card" style="border-color:#ffccc7;background:#fff2f0">{{ session('error') }}</div> @endif

    @if(empty($files))
      <p class="muted">No resources have been posted yet.</p>
    @else
      <div class="grid">
        @foreach($files as $f)
          <div class="card">
            <div class="name">{{ $f['name'] }}</div>
            <div class="meta">{{ $f['size'] }} · updated {{ $f['updated'] }}</div>
            <div>
              <a class="btn" href="{{ $f['url'] }}" target="_blank" rel="noopener">Open</a>
              <a class="btn" href="{{ $f['url'] }}" download>Download</a>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</body>
</html>