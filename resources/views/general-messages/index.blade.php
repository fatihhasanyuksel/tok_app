<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Messages — {{ ucfirst($type) }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin:0; padding:0; background:#fff; }
    .topbar { padding:12px 16px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; }
    .wrap { max-width:720px; margin:20px auto; padding:0 16px; }
    .list { display:flex; flex-direction:column; gap:12px; }
    .msg { padding:10px 14px; border-radius:10px; border:1px solid #ddd; max-width:80%; }
    .teacher { align-self:flex-end; background:#e8f1ff; border-color:#cfe0ff; color:#0a2e6c; }
    .student { align-self:flex-start; background:#f1f5f9; border-color:#e5eaf0; color:#222; }
    .meta { font-size:12px; color:#667; margin-top:4px; }
    textarea { width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; resize:vertical; font-family:inherit; }
    .btn { display:inline-block; padding:8px 14px; background:#0b6bd6; color:#fff; border:0; border-radius:8px; cursor:pointer; margin-top:6px; }
    .btn.secondary { background:#f5f5f5; color:#333; border:1px solid #ddd; }
  </style>
</head>
<body>
  <div class="topbar">
    <div>
      <strong>Messages · {{ ucfirst($type) }}</strong>
      <span style="font-size:14px; color:#555;">{{ $student->name }}</span>
    </div>
    <div>
      <a href="{{ route('workspace.show', ['type'=>$type,'student'=>$student->id]) }}" class="btn secondary">← Back to Workspace</a>
    </div>
  </div>

  <div class="wrap">
    @if(session('ok'))
      <div style="background:#e6ffed; border:1px solid #b7eb8f; padding:10px; border-radius:8px; margin-bottom:12px;">
        {{ session('ok') }}
      </div>
    @endif

    <div class="list">
      @forelse($messages as $m)
        @php
          $isTeacher = $m->sender_id !== $student->id;
          $class = $isTeacher ? 'teacher' : 'student';
          $sender = $m->sender->name ?? ($isTeacher ? 'Teacher' : 'Student');
        @endphp
        <div class="msg {{ $class }}">
          <div>{{ $m->body }}</div>
          <div class="meta">{{ $sender }} • {{ $m->created_at->diffForHumans() }}</div>
        </div>
      @empty
        <p style="color:#777;">No messages yet.</p>
      @endforelse
    </div>

    <form method="POST" action="{{ route('general.store', ['type'=>$type,'submission'=>$submission->id]) }}" style="margin-top:16px;">
      @csrf
      <textarea name="body" rows="3" placeholder="Write a message…"></textarea>
      <div style="display:flex; gap:8px; align-items:center;">
        <button type="submit" class="btn">Send</button>
        <a href="{{ route('workspace.show', ['type'=>$type,'student'=>$student->id]) }}" class="btn secondary">Close</a>
      </div>
    </form>
  </div>
</body>
</html>