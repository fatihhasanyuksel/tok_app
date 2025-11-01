<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>ToK Resources — Manage</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    :root { --bg:#f9fafb; --card:#fff; --border:#e5e7eb; --text:#111; --muted:#6b7280; --brand:#0a66ff; }
    * { box-sizing: border-box; }
    body { margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; background:var(--bg); color:var(--text); }
    header { background:var(--brand); color:#fff; padding:14px 20px; display:flex; align-items:center; justify-content:space-between; }
    header a { color:#fff; text-decoration:none; opacity:.9; }
    header a:hover { opacity:1; text-decoration:underline; }
    main { max-width:960px; margin: 20px auto; padding: 0 16px; }
    .flash { margin-bottom:12px; padding:10px 12px; border-radius:8px; }
    .ok { background:#e6ffed; border:1px solid #b7eb8f; }
    .err { background:#fff1f2; border:1px solid #fecaca; }
    .card { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:16px; box-shadow:0 1px 2px rgba(0,0,0,.04); }
    h1 { margin:0 0 8px; }
    .muted { color:var(--muted); }
    .grid { display:grid; grid-template-columns: 1fr; gap:16px; }
    @media (min-width: 800px) { .grid { grid-template-columns: 1fr 1fr; } }
    .upload { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .btn { background:var(--brand); color:#fff; border:0; border-radius:8px; padding:10px 14px; cursor:pointer; }
    .btn.ghost { background:#fff; color:#111; border:1px solid var(--border); }
    .list { margin-top:10px; border-top:1px solid var(--border); }
    .row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:10px 0; border-bottom:1px solid var(--border); }
    .name { font-weight:600; }
    .meta { color:var(--muted); font-size:12px; }
    .actions { display:flex; gap:8px; align-items:center; }
    a.link { color:var(--brand); text-decoration:none; }
    a.link:hover { text-decoration:underline; }
  </style>
</head>
<body>

<header>
  <div>ToK Resources — Manage</div>
  <nav>
    <a href="{{ route('resources.index') }}">View as student</a>
  </nav>
</header>

<main>
  @if(session('ok'))
    <div class="flash ok">{{ session('ok') }}</div>
  @endif
  @if(session('error'))
    <div class="flash err">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
    <div class="flash err">
      <strong>Upload error:</strong>
      <ul style="margin:6px 0 0 18px;">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="grid">
    <!-- Upload card -->
    <section class="card">
      <h1>Upload a file</h1>
      <p class="muted" style="margin-top:0">Max 20 MB. PDFs, Docs, images, etc.</p>
      <form class="upload" method="POST" action="{{ route('resources.upload') }}" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" required>
        <button class="btn" type="submit">Upload</button>
      </form>
    </section>

    <!-- Help / notes -->
    <section class="card">
      <h1>Tips</h1>
      <ul style="margin:8px 0 0 18px;">
        <li>Files are stored under <code>storage/app/public/tok</code> and served via <code>/storage/tok/…</code>.</li>
        <li>Use clear names, e.g., <em>tok-essay-rubric-2025.pdf</em>.</li>
        <li>Students see the list at <strong>/resources</strong>.</li>
      </ul>
    </section>
  </div>

  <!-- File list -->
  <section class="card" style="margin-top:16px;">
    <h1 style="margin-bottom:8px;">Files</h1>
    @if(empty($files) || count($files) === 0)
      <p class="muted">No files yet. Upload your first resource above.</p>
    @else
      <div class="list">
        @foreach($files as $f)
          <div class="row">
            <div>
              <div class="name"><a class="link" href="{{ $f['url'] }}" target="_blank" rel="noopener">{{ $f['name'] }}</a></div>
              <div class="meta">{{ $f['size'] }} • updated {{ $f['updated'] }}</div>
            </div>
            <div class="actions">
              <a class="btn ghost" href="{{ $f['url'] }}" target="_blank" rel="noopener">Open</a>
              <form method="POST" action="{{ route('resources.destroy', ['filename' => $f['name']]) }}" onsubmit="return confirm('Delete {{ $f['name'] }}?')">
                @csrf
                @method('DELETE')
                <button class="btn" type="submit">Delete</button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </section>
</main>

</body>
</html>