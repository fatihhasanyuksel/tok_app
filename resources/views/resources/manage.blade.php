<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage ToK Resources</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;background:#fff;color:#111;}
    .top{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid #eee;}
    .wrap{max-width:1000px;margin:0 auto;padding:18px;}
    .muted{color:#6b7280}
    .section{border:1px solid #e5e7eb;border-radius:12px;padding:14px;background:#fff;margin-bottom:16px}
    .row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid #d0d7de;border-radius:8px;background:#f8fafc;color:#111;text-decoration:none;cursor:pointer}
    .btn.danger{background:#fff2f0;border-color:#ffccc7;color:#a8071a}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{border:1px solid #e5e7eb;padding:10px;text-align:left;vertical-align:top}
    th{background:#f8fafc}
    code{background:#f6f8ff;border:1px solid #e5eaf0;border-radius:6px;padding:2px 6px}
  </style>
</head>
<body>
  <div class="top">
    <strong>Manage ToK Resources</strong>
    <div class="row">
      {{-- Back to role home (Students for teacher, Admin dashboard for admin) --}}
      @if(auth()->check() && auth()->user()->role === 'admin')
        <a class="btn" href="{{ route('admin.dashboard') }}">Admin dashboard</a>
      @elseif(auth()->check() && auth()->user()->role === 'teacher')
        <a class="btn" href="{{ route('students.index') }}">Students</a>
      @endif>

      <a class="btn" href="{{ route('resources.index') }}">Public view</a>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn" type="submit">Logout</button>
      </form>
    </div>
  </div>

  <div class="wrap">
    @if(session('ok'))   <div class="section" style="border-color:#b7eb8f;background:#e6ffed">{{ session('ok') }}</div> @endif
    @if(session('error'))<div class="section" style="border-color:#ffccc7;background:#fff2f0">{{ session('error') }}</div> @endif

    <div class="section">
      <h3 style="margin:0 0 8px">Upload resource</h3>
      <form method="POST" action="{{ route('resources.upload') }}" enctype="multipart/form-data" class="row">
        @csrf
        <input type="file" name="file" required>
        <button class="btn" type="submit">Upload</button>
        <span class="muted">Saved to <code>storage/app/public/tok/</code> → served at <code>/storage/tok/…</code></span>
      </form>
      @error('file')
        <div class="muted" style="color:#b00020;margin-top:6px;">{{ $message }}</div>
      @enderror
    </div>

    <div class="section">
      <h3 style="margin:0 0 8px">Existing files</h3>
      @if(empty($files))
        <p class="muted">No files uploaded yet.</p>
      @else
        <table>
          <thead>
            <tr>
              <th style="width:45%;">Name</th>
              <th style="width:15%;">Size</th>
              <th style="width:20%;">Updated</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($files as $f)
              <tr>
                <td style="word-break:break-word">{{ $f['name'] }}</td>
                <td>{{ $f['size'] }}</td>
                <td class="muted">{{ $f['updated'] }}</td>
                <td class="row">
                  <a class="btn" href="{{ $f['url'] }}" target="_blank" rel="noopener">Open</a>
                  <a class="btn" href="{{ $f['url'] }}" download>Download</a>

                  {{-- Delete uses route param {filename}; controller sanitizes it --}}
                  <form method="POST" action="{{ route('resources.destroy', ['filename' => $f['name']]) }}"
                        onsubmit="return confirm('Delete {{ $f['name'] }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn danger">Delete</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </div>
</body>
</html>