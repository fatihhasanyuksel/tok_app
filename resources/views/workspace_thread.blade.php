<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Workspace — {{ ucfirst($type) }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <style>
    :root{
      --bg:#ffffff; --border:#ececec; --muted:#6b7280;
      --teacher:#e7f0ff; --teacher-border:#cfe2ff;
      --student:#f3f4f6; --student-border:#e5e7eb; --badge:#eef;
      --btn:#0a66ff;
    }
    html,body{
      margin:0; padding:0; background:var(--bg);
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; color:#111;
    }
    .topbar{
      padding:14px 18px; border-bottom:1px solid var(--border);
      display:flex; align-items:center; justify-content:space-between; gap:12px;
    }
    .title{font-weight:700;}
    .chip{padding:4px 10px; border-radius:999px; background:var(--badge); font-size:12px; border:1px solid #dde;}
    .wrap{display:flex; height:calc(100vh - 58px);}
    .left{flex:1 1 58%; border-right:1px solid var(--border); padding:16px; overflow:auto;}
    .right{flex:1 1 42%; padding:16px; overflow:auto; background:#fafafa;}
    textarea{width:100%; height:65vh; font-family:inherit; padding:10px; border:1px solid #ccc; border-radius:8px; resize:vertical;}

    .thread-card{background:#fff; border:1px solid var(--border); border-radius:12px; padding:14px;}
    .thread-head{display:flex; justify-content:space-between; gap:8px; align-items:center; flex-wrap:wrap;}
    .status{border:1px solid #dde; background:#f6f8ff; padding:3px 9px; border-radius:999px; font-size:12px; text-transform:capitalize;}
    .sel{color:#555; font-style:italic; margin-top:6px;}

    /* Messages area */
    #messagesBox{margin-top:10px; height:48vh; overflow:auto; padding:2px 4px; scroll-behavior:smooth;}
    .msg-row{display:flex; margin:6px 0; align-items:flex-start;}
    .msg-row.teacher{justify-content:flex-start;}
    .msg-row.student{justify-content:flex-end;}

    /* Compact bubbles */
    .bubble{
      display:inline-block; max-width:75%; box-sizing:border-box;
      border-radius:10px; padding:6px 10px; border:1px solid transparent;
      line-height:1.4; text-align:left; margin:2px 0; background-clip:padding-box;
    }
    .teacher .bubble{background:var(--teacher); border-color:var(--teacher-border);}
    .student .bubble{background:var(--student); border-color:var(--student-border);}

    /* IMPORTANT: only the message text preserves newlines */
    .bubble .msg{
      white-space:pre-wrap;           /* preserve user newlines */
      word-wrap:break-word;
      display:block;
    }
    .bubble .meta{
      display:block; font-size:12px; color:var(--muted); margin-top:3px;
    }

    /* Reply area */
    .reply{margin-top:10px;}
    .reply textarea{height:120px; background:#fff;}

    /* Buttons */
    .btn{padding:10px 14px; border:none; background:var(--btn); color:#fff; border-radius:8px; cursor:pointer;}
    .btn.sm{padding:6px 10px; border-radius:6px; font-size:14px;}
    .btn.ghost{background:#fff; color:#333; border:1px solid #ddd;}
    .btn:disabled{opacity:.5; cursor:not-allowed;}

    /* Status buttons row */
    .status-actions{display:flex; gap:6px; flex-wrap:wrap; margin-top:8px;}
    .status-btn{padding:6px 10px; border-radius:8px; border:1px solid #e5e7eb; background:#fafafa; cursor:pointer; font-size:12px;}
    .status-btn.is-active{ background:#e7f0ff; border-color:#cfe2ff; color:#0a2e6c; }

    .flash-ok{background:#e6ffed; border:1px solid #b7eb8f; padding:10px; margin:12px 0; border-radius:8px;}

    /* typing indicator */
    #typingIndicator{ font-size:12px; color:#6b7280; margin:6px 4px 0; display:none; }
  </style>
</head>
<body>

  <div class="topbar">
    <div class="title">Workspace: {{ ucfirst($type) }}</div>
    <div class="chip">{{ $student->name }}</div>
  </div>

  @if(session('ok'))
    <div class="flash-ok" style="margin:12px 16px 0">{{ session('ok') }}</div>
  @endif

  <div class="wrap">
    <!-- Left: editor -->
    <div class="left">
      <h2>Draft Editor</h2>
      <form method="POST" action="{{ route('workspace.save', ['type'=>$type, 'student'=>$student->id]) }}">
        @csrf
        <textarea name="body">{{ strip_tags($latestVersion->body_html) }}</textarea>
        <p><button type="submit" class="btn">💾 Save Draft</button></p>
      </form>
    </div>

    <!-- Right: thread with chat bubbles -->
    <div class="right">
      <h2 style="margin-top:0">Feedback Threads</h2>

      <div class="thread-card" id="threadCard">
        <!-- Header + selection + live status -->
        <div class="thread-head">
          <div>
            <div style="font-weight:700">Thread #{{ $thread->id }}</div>
            @if($thread->selection_text)
              <div class="sel">“{{ \Illuminate\Support\Str::limit($thread->selection_text, 160) }}”</div>
            @endif
          </div>

          <div style="display:flex;align-items:center;gap:10px;">
            <span class="status" id="statusPill">{{ $thread->status ?? 'open' }}</span>
          </div>
        </div>

        <!-- Status buttons (AJAX) -->
        @php $statuses = ['open','seen','revised','approved','reopened','outdated']; @endphp
        <div class="status-actions" id="statusButtons">
          @foreach($statuses as $s)
            <button type="button"
                    class="status-btn {{ ($thread->status ?? 'open') === $s ? 'is-active' : '' }}"
                    data-status="{{ $s }}">
              {{ ucfirst($s) }}
            </button>
          @endforeach
        </div>

        <!-- Messages -->
        <div id="messagesBox">
          @foreach($thread->messages as $m)
            @php
              $isStudent = ($m->author_id === $student->id);
              $whoClass  = $isStudent ? 'student' : 'teacher';
              $whoName   = $m->author->name ?? ($isStudent ? 'Student' : 'Teacher');
            @endphp
            <div class="msg-row {{ $whoClass }}">
              <div class="bubble">
                <div class="msg">{!! nl2br(e(rtrim($m->body))) !!}</div>
                <div class="meta">
                  <span>{{ $whoName }}</span>
                  <span> • {{ $m->created_at?->diffForHumans() }}</span>
                </div>
              </div>
            </div>
          @endforeach
        </div>

        <!-- Typing indicator -->
        <div id="typingIndicator"></div>

        <!-- Reply -->
        <div class="reply">
          <form method="POST"
                action="{{ route('thread.reply', ['type'=>$type, 'thread'=>$thread->id, 'student'=>request('student')]) }}"
                id="replyForm">
            @csrf
            <textarea name="body" placeholder="Write a reply…" id="replyBox"></textarea>
            <div style="display:flex;gap:8px;align-items:center;margin-top:8px;">
              <button type="submit" class="btn sm">Send</button>
              <a class="btn sm ghost" href="{{ route('workspace.show', ['type'=>$type, 'student'=>request('student')]) }}">Close</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Auto-scroll to bottom for messages
    (function(){
      const box = document.getElementById('messagesBox');
      if (!box) return;
      const toBottom = () => { box.scrollTop = box.scrollHeight + 999; };
      toBottom();
      window.addEventListener('load', toBottom);
      new ResizeObserver(toBottom).observe(box);
    })();

    // Poll every 10s for new messages
    (function(){
      const box = document.getElementById('messagesBox');
      if (!box) return;

      let lastId = {{ $thread->messages->count() ? (int)$thread->messages->last()->id : 0 }};
      const pollUrl = "{{ route('thread.poll', ['type' => $type, 'thread' => $thread->id, 'student' => request('student')]) }}";

      async function poll(){
        try {
          const res = await fetch(pollUrl + '?after=' + encodeURIComponent(lastId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });
          if (!res.ok) return;
          const data = await res.json();
          if (data && data.ok) {
            box.innerHTML = data.html;
            lastId = data.last_id || lastId;
            box.scrollTop = box.scrollHeight + 999;
          }
        } catch(e) {}
      }
      setInterval(poll, 10000);
    })();

    // Status buttons (AJAX)
    (function () {
      const statusUrl = "{{ route('thread.status', ['type' => $type, 'thread' => $thread->id, 'student' => request('student')]) }}";
      const pill = document.getElementById('statusPill');
      const buttonsWrap = document.getElementById('statusButtons');
      if (!buttonsWrap || !pill) return;

      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

      function setActive(status){
        pill.textContent = status;
        buttonsWrap.querySelectorAll('.status-btn').forEach(b => {
          b.classList.toggle('is-active', b.dataset.status === status);
          b.disabled = false;
        });
      }

      buttonsWrap.addEventListener('click', async (e) => {
        const btn = e.target.closest('.status-btn');
        if (!btn) return;

        const newStatus = btn.dataset.status;
        if (btn.classList.contains('is-active')) return;

        // Optimistic UI
        buttonsWrap.querySelectorAll('.status-btn').forEach(b => b.disabled = true);
        pill.textContent = newStatus;
        btn.classList.add('is-active');

        try {
          const res = await fetch(statusUrl, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrf,
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json',
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ status: newStatus })
          });

          if (!res.ok) throw new Error('HTTP ' + res.status);
          const data = await res.json();
          if (!data.ok) throw new Error(data.error || 'Update failed');

          setActive(data.status);
        } catch (err) {
          alert('Could not update status. Please try again.');
          setActive({{ json_encode($thread->status ?? 'open') }});
        }
      });
    })();

    // Typing indicator (lightweight polling)
    (function () {
      const typingUrl = "{{ route('thread.typing', ['type' => $type, 'thread' => $thread->id, 'student' => request('student')]) }}";
      const typingStatusUrl = "{{ route('thread.typingStatus', ['type' => $type, 'thread' => $thread->id, 'student' => request('student')]) }}";
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const replyBox = document.getElementById('replyBox');
      const typingEl = document.getElementById('typingIndicator');

      if (!replyBox || !typingEl) return;

      let lastPingAt = 0;
      const PING_EVERY_MS = 4000;

      replyBox.addEventListener('input', () => {
        const now = Date.now();
        if (now - lastPingAt < PING_EVERY_MS) return;
        lastPingAt = now;

        fetch(typingUrl, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        }).catch(()=>{});
      });

      async function pollTyping() {
        try {
          const res = await fetch(typingStatusUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });
          if (!res.ok) return;
          const data = await res.json();
          if (!data || !data.ok) return;

          const names = Array.isArray(data.typing_by) ? data.typing_by.filter(Boolean) : [];
          if (names.length === 0) {
            typingEl.style.display = 'none';
            typingEl.textContent = '';
            return;
          }

          let msg = '';
          if (names.length === 1)       msg = `${names[0]} is typing…`;
          else if (names.length === 2)  msg = `${names[0]} and ${names[1]} are typing…`;
          else                          msg = `${names.length} people are typing…`;

          typingEl.textContent = msg;
          typingEl.style.display = 'block';
        } catch (_) {}
      }

      setInterval(pollTyping, 3000);
    })();
  </script>
</body>
</html>