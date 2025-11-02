<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Workspace — {{ ucfirst($type) }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    html, body { margin:0; padding:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; height:100%; background:#fff; }
    .topbar { padding:12px 16px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; gap:12px; position:relative; }
    .badge { padding:4px 8px; border-radius:999px; background:#eef; font-size:12px; border:1px solid #dde; }
    .wrap { display:flex; height:calc(100vh - 50px); }
    .left { position:relative; flex:1 1 60%; padding:16px; overflow:auto; border-right:1px solid #eee; }
    .right { flex:1 1 40%; padding:16px; overflow:auto; background:#fafafa; transition:all .25s ease; will-change:opacity,width,padding; }
    .sup-pill{ margin-left:8px; padding:4px 10px; border-radius:999px; background:#fff7e6; border:1px solid #ffe7ba; color:#8a5a00; font-size:12px; }

    textarea.input, textarea.editor { width:100%; font-family:inherit; padding:10px; border:1px solid #ccc; border-radius:8px; resize:vertical; }
    textarea.editor { height:65vh; }

    .btn { display:inline-flex; align-items:center; gap:6px; padding:10px 14px; border:0; background:#0b6bd6; color:#fff; border-radius:8px; cursor:pointer; text-decoration:none; }
    .btn.secondary { background:#f5f5f5; color:#333; border:1px solid #ddd; }
    .btn.sm { padding:6px 10px; border-radius:6px; font-size:14px; }
    .pill { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#f6f8ff; border:1px solid #e5eaf0; font-size:13px; color:#0a2e6c; }
    .pill .dot { min-width:18px; height:18px; padding:0 6px; display:inline-flex; align-items:center; justify-content:center; border-radius:999px; background:#0b6bd6; color:#fff; font-size:12px; }

    .thread-card { background:#fff; border:1px solid #ddd; border-radius:12px; padding:14px; }
    .status-pill { font-size:12px; border:1px solid #dde; border-radius:999px; padding:2px 8px; text-transform:capitalize; }
    .bubble-list { display:flex; flex-direction:column; gap:10px; max-height:55vh; overflow:auto; padding:6px 10px; background:#f7f7f8; border:1px solid #eee; border-radius:10px; }
    .row { display:flex; width:100%; }
    .row.student { justify-content:flex-start; }
    .row.teacher { justify-content:flex-end; }
    .row .bubble { max-width:85%; padding:10px 12px; border-radius:14px; border:1px solid transparent; }
    .bubble.student { background:#f1f5f9; border-color:#e5eaf0; color:#222; }
    .bubble.teacher { background:#e8f1ff; border-color:#cfe0ff; color:#0a2e6c; }
    .sel, .sel-quote{ margin-top:8px; background:#fff7a8; padding:8px 10px; border:1px solid #f2e38b; border-radius:8px; color:#333; font-size:13px; white-space:pre-wrap; }
    .muted { color:#667085; }
    .list { display:flex; flex-direction:column; gap:10px; }

    /* History modal */
    #hist-backdrop { position:fixed; inset:0; background:rgba(0,0,0,.35); display:none; align-items:center; justify-content:center; z-index:10000; }
    #hist-dialog { width:720px; max-width:90vw; max-height:80vh; background:#fff; border-radius:12px; box-shadow:0 18px 40px rgba(0,0,0,.18); display:flex; flex-direction:column; }
    #hist-header { padding:12px 16px; border-bottom:1px solid #eee; display:flex; align-items:center; justify-content:space-between; gap:8px; }
    #hist-body { padding:12px 16px; overflow:auto; }
    .hist-row { border:1px solid #eee; border-radius:10px; padding:10px; margin:8px 0; display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .hist-meta { font-size:13px; color:#555; }
    .hist-snippet { font-size:13px; color:#333; margin-top:6px; max-height:5.2em; overflow:hidden; }

    /* Messages dropdown */
    #msg-panel { display:none; position:absolute; right:16px; top:56px; width:360px; max-height:60vh; overflow:auto; background:#fff; border:1px solid #e5e5e5; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.12); z-index:9999; padding:12px; }

    /* TipTap UI */
    .tt-toolbar { display:flex; flex-wrap:wrap; gap:6px; padding:6px; border:1px solid #ddd; border-radius:8px 8px 0 0; background:#f8fafc; }
    .tt-btn { height:30px; padding:0 10px; border:1px solid #d0d7de; background:#fff; border-radius:6px; font-size:13px; cursor:pointer; }
    .tt-btn[disabled] { opacity:.5; cursor:not-allowed; }
    .tt-btn.is-active { background:#e8f1ff; border-color:#cfe0ff; color:#0a2e6c; }
    .tt-sel { height:30px; padding:0 8px; border:1px solid #d0d7de; border-radius:6px; background:#fff; font-size:13px; }
    .tt-wrap { border:1px solid #ddd; border-radius:8px; }
    .tt-editor { min-height:65vh; padding:10px; border-top:1px solid #ddd; border-radius:0 0 8px 8px; }
    .tt-editor:focus { outline:none; }

    .tt-editor img { max-width:100%; height:auto; border-radius:6px; }

    [hidden] { display:none !important; visibility:hidden !important; }
    #editor.force-hidden { display:none !important; }
  </style>
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>

<body
  x-data="{
    threadsOpen: JSON.parse(localStorage.getItem('threadsOpen') ?? 'true'),
    toggleThreads() { this.threadsOpen = !this.threadsOpen; localStorage.setItem('threadsOpen', JSON.stringify(this.threadsOpen)); }
  }"
>
@php
  $studentIdParam   = request('student') ?? ($student->id ?? null);
  $routeParamsBase  = ['type' => $type] + ($studentIdParam ? ['student' => $studentIdParam] : []);
  $plain            = trim(preg_replace('/\s*<br\s*\/?>\s*/i', "\n", strip_tags($latestVersion->body_html ?? '')));
  $initialText      = old('body', $submission->working_body ?? $plain);
  $initialHtml      = old('body_html', $submission->working_html ?? ($latestVersion->body_html ?? ''));
  $createThreadUrl  = route('thread.create', ['type'=>$type] + ($studentIdParam ? ['student'=>$studentIdParam] : []));
  $role             = optional(Auth::user())->role;
  $isStaff          = in_array($role, ['teacher','admin'], true);
@endphp

<div class="topbar" id="topbar">
  <div style="display:flex; align-items:center; gap:8px;">
    <strong>Workspace: {{ ucfirst($type) }}</strong>
    <span class="badge">{{ $student->name }}</span>
    @php
      $isStudent = ($role === 'student');
      $supervisorName = $isStudent
        ? (optional($student->teacher)->name
            ?? \DB::table('teachers')->where('id', $student->teacher_id)->value('name'))
        : null;
    @endphp
    @if($isStudent)
      <span class="sup-pill">
        Your ToK Supervisor: <strong>{{ $supervisorName ?: 'Unassigned' }}</strong>
      </span>
    @endif
  </div>

  <div style="display:flex; align-items:center; gap:8px;">
    <button type="button" class="pill" id="msg-toggle"><span>💬 Messages</span></button>
    <button type="button" class="btn secondary sm" @click="toggleThreads()" x-text="threadsOpen ? '▸ Hide threads' : '◂ Show threads'"></button>
    <a class="btn secondary sm" style="margin-left:10px;" href="{{ route('resources.index') }}">📚 ToK Resources</a>

    @php
      $dashRoute = match ($role) {
        'student' => 'student.dashboard',
        'teacher' => 'students.index',
        'admin'   => 'admin.dashboard',
        default   => null,
      };
    @endphp
    @if ($dashRoute)
      @unless (request()->routeIs($dashRoute))
        <a class="btn secondary sm" href="{{ route($dashRoute) }}">Dashboard</a>
      @endunless
    @endif

    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
      @csrf
      <button type="submit" class="btn secondary sm">Logout</button>
    </form>
  </div>

  <div id="msg-panel">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px;">
      <strong>Messages</strong>
      <button type="button" class="btn secondary sm" id="msg-close" style="padding:4px 8px;">Close</button>
    </div>
    <div id="msg-list"><p class="muted" style="margin:6px 0;">Loading…</p></div>
    <form id="msg-form" style="margin-top:10px; display:none;">
      @csrf
      <textarea name="body" rows="3" placeholder="Type a message…" required
        style="width:100%; padding:8px; border:1px solid #ccc; border-radius:8px;"></textarea>
      <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px;">
        <button type="submit" class="btn">Send</button>
      </div>
    </form>
  </div>
</div>

<div class="wrap">
  {{-- LEFT EDITOR --}}
  <div class="left"
       x-data="commentFromSelection('{{ $createThreadUrl }}', {{ $isStaff ? 'true' : 'false' }})"
       x-init="boot()">
    <form method="POST" action="{{ route('workspace.save', $routeParamsBase) }}" style="margin-bottom:10px;">
      @csrf
      <h2>Draft Editor</h2>

      <div class="tt-wrap">
        <div class="tt-toolbar" id="tt-toolbar">
          <select id="tt-heading" class="tt-sel" title="Heading">
            <option value="p">Paragraph</option>
            <option value="h1">Heading 1</option>
            <option value="h2">Heading 2</option>
            <option value="h3">Heading 3</option>
          </select>
          <button type="button" class="tt-btn" data-cmd="toggleBold" title="Bold (⌘B)">B</button>
          <button type="button" class="tt-btn" data-cmd="toggleItalic" title="Italic (⌘I)"><i>I</i></button>
          <button type="button" class="tt-btn" data-cmd="toggleUnderline" title="Underline">U</button>
          <button type="button" class="tt-btn" data-cmd="toggleStrike" title="Strike">S</button>
          <button type="button" class="tt-btn" data-cmd="toggleBulletList" title="Bulleted list">• List</button>
          <button type="button" class="tt-btn" data-cmd="toggleOrderedList" title="Numbered list">1. List</button>
          <button type="button" class="tt-btn" data-cmd="toggleBlockquote" title="Blockquote">“ ”</button>
          <button type="button" class="tt-btn" data-cmd="toggleCode" title="Inline code">code</button>
          <button type="button" class="tt-btn" id="tt-link" title="Insert/Edit link">🔗</button>
          <button type="button" class="tt-btn" id="tt-image" title="Insert image">🖼️</button>
          <button type="button" class="tt-btn" data-cmd="undo" title="Undo">↶</button>
          <button type="button" class="tt-btn" data-cmd="redo" title="Redo">↷</button>
          <input type="file" id="tt-image-input" accept="image/*" style="display:none">
        </div>
        <div id="rt-editor" class="tt-editor"></div>
      </div>

      <!-- Single source of truth for HTML (unescaped) -->
      <textarea id="body_html" name="body_html" hidden>{!! $initialHtml !!}</textarea>

      <!-- Plain text mirror (kept for legacy paths / fallback) -->
      <textarea
        id="editor"
        name="body"
        hidden
        aria-hidden="true"
        tabindex="-1"
        class="force-hidden"
      >{{ $initialText }}</textarea>

      <!-- Actions -->
      <div style="display:flex; gap:12px; align-items:flex-end; margin-top:8px; flex-wrap:wrap;">
        <div style="display:flex; gap:8px; align-items:center;">
          <button type="submit" class="btn">💾 Save Draft</button>

          @if($isStaff)
            <a href="{{ route('workspace.export', $routeParamsBase) }}"
               class="btn secondary"
               target="_blank"
               rel="noopener"
               title="Open a printable export in new tab">⬇️ Export</a>
          @endif

          <button type="button" class="btn secondary" id="history-btn">🕘 History</button>

          @if($isStaff)
            <button type="button"
                    class="btn secondary"
                    @click="prepSelection()"
                    title="Comment on selected text in student draft">💬 Comment Selection</button>
          @else
            <button type="button"
                    class="btn secondary"
                    id="req-feedback-btn"
                    title="Request feedback from your teacher">🙋 Request feedback</button>
          @endif
        </div>

        @if($isStaff)
          <div style="display:flex; gap:8px; align-items:center;">
            <label style="display:flex; gap:8px; align-items:center; font-size:13px; color:#0a2e6c; border:1px solid #e5eaf0; padding:8px 10px; border-radius:8px; background:#f6f8ff; cursor:pointer;">
              <input type="checkbox" name="milestone" value="1">
              <span>Mark as Milestone</span>
            </label>
            <input type="text" name="milestone_note" placeholder="Optional note (e.g., 'First full draft')" maxlength="140"
                   style="min-width:260px; padding:8px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px;">
          </div>
        @endif

        <span id="autosave-status" class="pill" style="margin-left:auto; display:none;" aria-live="polite">Saving…</span>
      </div>
    </form>

    <!-- Selection composer -->
    <template x-if="showComposer">
      <div class="sel-toolbar" style="position:sticky; bottom:20px;">
        <div class="sel-card">
          <div class="label">Commenting on selection</div>
          <div class="sel-quote" x-text="selectionText"></div>
          <form :action="postUrl" method="POST" style="margin-top:8px; display:flex; gap:8px; align-items:flex-end;">
            @csrf
            <input type="hidden" name="submission_id" value="{{ $submission->id }}">
            <input type="hidden" name="selection_text" :value="selectionText">
            <input type="hidden" name="start_offset" :value="startOffset">
            <input type="hidden" name="end_offset"   :value="endOffset">
            <textarea name="body" rows="2" placeholder="Write your feedback…" required style="flex:1; font-family:inherit; font-size:14px; border:1px solid #cbd5e1; border-radius:8px; padding:8px;"></textarea>
            <button type="submit" class="btn sm">Start thread</button>
            <button type="button" class="btn secondary sm" @click="cancel()">Cancel</button>
          </form>
        </div>
      </div>
    </template>
  </div>

  {{-- RIGHT PANEL --}}
  <div class="right" x-data="hybridPane()" x-init="window.hyPane = this"
       x-show="threadsOpen" x-transition.opacity.duration.150ms
       @hybrid-list.window="mode='list'">
    <div>
      <div style="display:flex; align-items:center; justify-content:space-between; margin:0 0 10px;">
        <h2 style="margin:0" x-text="mode === 'list' ? 'Feedback Threads' : 'Feedback'"></h2>
        <button class="btn secondary sm" @click="mode='list'" x-show="mode==='thread'">← All threads</button>
      </div>

      <template x-if="mode === 'list'">
        <div>
          @if(isset($threads) && $threads->count())
            <div class="list">
              @foreach ($threads as $t)
                @php
                  $resolved = (bool) ($t->is_resolved ?? false);
                  $st = strtolower($t->status ?? 'open');

                  $colorMap = [
                    'open'     => ['#e8f1ff', '#0a2e6c'],
                    'seen'     => ['#e8f1ff', '#0a2e6c'],
                    'revised'  => ['#fff4e5', '#8a5a00'],
                    'approved' => ['#e6ffed', '#135f26'],
                    'closed'   => ['#e6ffed', '#135f26'],
                  ];

                  $uiMap = [
                    'open'     => ['Awaiting Student', 'open'],
                    'seen'     => ['Awaiting Student', 'open'],
                    'revised'  => ['Awaiting Teacher', 'revised'],
                    'closed'   => ['Resolved',         'closed'],
                    'approved' => ['Resolved',         'closed'],
                  ];

                  if ($resolved) {
                      $label = 'Resolved';
                      [$bg, $fg] = $colorMap['closed'];
                  } else {
                      [$label, $colorKey] = $uiMap[$st] ?? [ucfirst($st), $st];
                      [$bg, $fg] = $colorMap[$colorKey] ?? ['#f6f8ff', '#334'];
                  }
                @endphp

                <div class="thread-card">
                  <div>
                    <div style="font-weight:600;">
                      Feedback
                      @if(!empty($t->selection_text))
                        <span class="muted">on “{{ \Illuminate\Support\Str::limit($t->selection_text, 60) }}”</span>
                      @endif
                      <span class="muted" title="{{ optional($t->created_at)->setTimezone('Asia/Dubai')->format('Y-m-d H:i') }}"> · {{ $t->created_at?->diffForHumans() }}</span>
                    </div>

                    <!-- status pill honors is_resolved first -->
                    <span class="status-pill" style="background:{{ $bg }}; color:{{ $fg }}; border-color:{{ $bg }}">
                      {{ $label }}
                    </span>

                    @if(!empty($t->selection_text))
                      <div class="sel" style="margin-top:6px;"><em>“{{ \Illuminate\Support\Str::limit($t->selection_text, 140) }}”</em></div>
                    @endif
                  </div>

                  <div style="margin-top:10px;">
                    <button class="btn secondary sm"
                            @click="openThread({ id: {{ $t->id }}, url: '{{ route('thread.show', ['type'=>$type, 'thread'=>$t->id] + $routeParamsBase) }}' })">
                      Open
                    </button>
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <p class="muted">No feedback threads yet.</p>
          @endif
        </div>
      </template>

      <template x-if="mode === 'thread'">
        <div id="threadMount">
          @isset($thread)
            @include('partials.thread')
          @endisset
        </div>
      </template>
    </div>
  </div>
</div>

<!-- ===== History Modal ===== -->
<div id="hist-backdrop" role="dialog" aria-modal="true" aria-labelledby="hist-title">
  <div id="hist-dialog">
    <div id="hist-header">
      <strong id="hist-title">Version History</strong>
      <label style="display:flex; align-items:center; gap:8px; font-size:13px;">
        <input type="checkbox" id="hist-only-milestones">
        <span>Milestones only</span>
      </label>
      <button type="button" class="btn secondary sm" id="hist-close">Close</button>
    </div>
    <div id="hist-body"><p class="muted">Loading…</p></div>
  </div>
</div>

<script>
/* TipTap-aware selection → comment composer */
function commentFromSelection(postUrl, isStaff){
  return {
    postUrl, isStaff: !!isStaff, showComposer:false, selectionText:'', startOffset:'', endOffset:'',
    boot(){ /* no-op */ },
    prepSelection(){
      if (!this.isStaff) { alert('Only teachers can start feedback threads.'); return; }
      const ed = window.TIPTAP; if (!ed) { alert('Editor not ready yet.'); return; }
      const { from=0, to=0 } = ed.state.selection || {};
      if (from === to) { alert('Select some text first.'); return; }
      const before = ed.state.doc.textBetween(0, from, '\n');
      const slice  = ed.state.doc.textBetween(from, to, '\n');
      this.selectionText = slice.slice(0,255);
      this.startOffset   = before.length;
      this.endOffset     = this.startOffset + slice.length;
      this.showComposer  = true;
    },
    cancel(){ this.showComposer=false; this.selectionText=''; this.startOffset=''; this.endOffset=''; }
  }
}
function hybridPane(){
  return {
    mode:'list', current:null, loading:false,
    async openThread(th){
      this.current=th; this.mode='thread'; this.loading=true;
      try{
        const url = th.url + (th.url.includes('?') ? '&' : '?') + 'partial=1';
        const r=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest'}});
        if(!r.ok){ location.href=th.url; return; }
        const html=await r.text();
        const mount = document.getElementById('threadMount');
        if (mount){ mount.innerHTML=html; if (window.Alpine && Alpine.initTree) Alpine.initTree(mount); }
      }catch(_){ location.href=th.url; }
      finally{ this.loading=false; }
    }
  }
}
</script>

<script>
/* Messages + autosave + history (kept same structure; autosave now watches HTML mirror) */
window.MSG = { type: '{{ $type }}', submissionId: {{ $submission->id }} };
(function () {
  const panel   = document.getElementById('msg-panel');
  const toggle  = document.getElementById('msg-toggle');
  const closeBt = document.getElementById('msg-close');
  const list    = document.getElementById('msg-list');
  const form    = document.getElementById('msg-form');
  if (!panel || !toggle || !list || !form) return;
  const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  function fmtDubai(iso){ try{ if(!iso) return ''; return new Date(iso).toLocaleString('en-GB',{timeZone:'Asia/Dubai',hour12:false}); }catch(_){ return iso||''; } }
  function ctx(){ return (window.MSG&&window.MSG.type&&window.MSG.submissionId)?{ok:true,type:window.MSG.type,sid:window.MSG.submissionId}:{ok:false}; }
  async function fetchJSON(url){ const res=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}}); if(!res.ok) throw new Error('HTTP '+res.status); return res.json(); }
  function render(messages){
    if(!messages||!messages.length){ list.innerHTML='<p class="muted" style="margin:6px 0;">No messages yet.</p>'; return; }
    list.innerHTML = messages.map(m=>{
      const when=m.created_at?fmtDubai(m.created_at):'';
      const body=(m.body||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
      return `<div class="msg" style="border:1px solid #eee;border-radius:8px;padding:8px;margin:8px 0;">
                <div style="color:#666;font-size:12px;margin-bottom:4px;" title="${m.created_at||''}">${when}</div>
                <div>${body}</div>
              </div>`;
    }).join('');
    list.scrollTop=list.scrollHeight;
  }
  async function loadMessages(){
    const c=ctx();
    if(!c.ok){ list.innerHTML='<p class="muted" style="margin:6px 0;">Open a workspace item to view messages.</p>'; form.style.display='none'; return; }
    list.innerHTML='<p class="muted" style="margin:6px 0;">Loading…</p>';
    try{ const data=await fetchJSON(`/workspace/${encodeURIComponent(c.type)}/general/${encodeURIComponent(c.sid)}`); render(data.messages||[]); form.style.display=''; }
    catch(e){ console.error(e); list.innerHTML='<p class="muted" style="color:#b00; margin:6px 0;">Failed to load messages.</p>'; form.style.display='none'; }
  }
  async function send(body){
    const c=ctx(); if(!c.ok) return;
    const res=await fetch(`/workspace/${encodeURIComponent(c.type)}/general/${encodeURIComponent(c.sid)}`,{
      method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}, body:JSON.stringify({body})
    });
    if(!res.ok) throw new Error('HTTP '+res.status); return res.json();
  }
  toggle.addEventListener('click',(e)=>{ e.preventDefault(); if(panel.style.display==='none'||panel.style.display===''){ panel.style.display='block'; loadMessages(); } else { panel.style.display='none'; } });
  closeBt.addEventListener('click',()=>{ panel.style.display='none'; });
  form.addEventListener('submit', async (e)=>{ e.preventDefault(); const ta=form.querySelector('textarea[name="body"]'); const body=(ta.value||'').trim(); if(!body) return;
    try{ await send(body); ta.value=''; await loadMessages(); }catch(err){ console.error(err); alert('Failed to send message.'); } });

  document.addEventListener('click',(evt)=>{ const topbar=document.getElementById('topbar'); const inside=topbar && topbar.contains(evt.target); if(!inside) panel.style.display='none'; });

  const reqBtn=document.getElementById('req-feedback-btn');
  if(reqBtn){
    reqBtn.addEventListener('click', async ()=>{
      try{
        reqBtn.disabled=true;
        let snippet='';
        const ed=window.TIPTAP;
        if(ed){
          const {from,to}=ed.state.selection||{};
          if(from!==undefined && to!==undefined && from!==to){ snippet=ed.state.doc.textBetween(from,to,'\n').trim(); }
        }
        const fallback=document.getElementById('editor');
        if(!snippet && fallback){ const s=fallback.selectionStart??0, e=fallback.selectionEnd??0; snippet=(fallback.value??'').substring(s,e).trim(); }
        const msg = snippet ? `Requesting feedback on:\n“${snippet.slice(0,300)}”` : 'Hi, could you review my latest changes?';
        const res=await fetch(`/workspace/{{ $type }}/general/{{ $submission->id }}`,{
          method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
          body:JSON.stringify({ body: msg })
        });
        if(!res.ok) throw new Error('HTTP '+res.status);
        reqBtn.textContent='✅ Requested'; setTimeout(()=>{ reqBtn.textContent='🙋 Request feedback'; reqBtn.disabled=false; },2500);
      }catch(e){ console.error(e); alert('Could not send request right now.'); reqBtn.disabled=false; }
    });
  }

  /* Autosave — watches HTML mirror */
  (function autosaveSetup(){
    const editorPlain = document.getElementById('editor');
    const editorHtml  = document.getElementById('body_html');   // <-- unified id
    const badge=document.getElementById('autosave-status');
    if(!editorHtml||!badge) return;

    const ownerType="{{ $type }}";
    const ownerId={{ $submission->id }};
    const autosaveUrl=`/api/tok/docs/${ownerType}/${ownerId}`;

    let timer=null, lastSaved=editorHtml.value, inFlight=false, lastSaveTs=0, queued=false;
    const THROTTLE_MS=20000;

    function show(text){ badge.style.display=''; badge.textContent=text; }
    function hideSoon(){ setTimeout(()=>{ badge.style.display='none'; },1000); }

    async function doSave(throttled=true){
      if(inFlight){ queued=true; return; }
      const bodyHtml = editorHtml.value;
      if(bodyHtml===lastSaved) return;

      if(throttled){
        const now=Date.now(), since=now-lastSaveTs;
        if(since<THROTTLE_MS){ queued=true; const wait=Math.max(THROTTLE_MS-since,1); if(timer) clearTimeout(timer); timer=setTimeout(()=>doSave(true),wait); return; }
      }

      inFlight=true; show('Saving…');
      try{
        const payload = { html: bodyHtml, plain: editorPlain ? editorPlain.value : undefined };
        const res=await fetch(autosaveUrl,{
          method:'PATCH', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body:JSON.stringify(payload)
        });
        if(!res.ok) throw new Error('HTTP '+res.status);
        lastSaved=bodyHtml; lastSaveTs=Date.now(); show('Saved'); hideSoon();
      }catch(e){ console.error(e); show('Save failed — will retry'); }
      finally{ inFlight=false; if(queued){ queued=false; setTimeout(()=>doSave(true),0); } }
    }
    function scheduleSave(){ show('Saving…'); if(timer) clearTimeout(timer); timer=setTimeout(()=>doSave(true),1200); }

    editorHtml.addEventListener('input',scheduleSave);
    editorHtml.addEventListener('blur',()=>doSave(false));
    document.addEventListener('keydown',(e)=>{ if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'){ e.preventDefault(); doSave(false); } });
    document.addEventListener('visibilitychange',()=>{ if(document.visibilityState==='hidden') doSave(false); });
    window.addEventListener('beforeunload',()=>{ doSave(false); });
  })();

  /* History modal (restore pushes TipTap HTML) */
  (function historySetup(){
    const btn=document.getElementById('history-btn');
    const backDrop=document.getElementById('hist-backdrop');
    const closeBtn=document.getElementById('hist-close');
    const bodyEl=document.getElementById('hist-body');
    const onlyMs=document.getElementById('hist-only-milestones');
    const editorPlain=document.getElementById('editor');
    const editorHtml=document.getElementById('body_html');      // <-- unified id
    const csrf=(document.querySelector('meta[name="csrf-token"]')||{}).content||'';
    if(!btn||!backDrop||!closeBtn||!bodyEl) return;

    const historyUrl="{{ route('workspace.history', $routeParamsBase) }}";
    let cache=null;

    function formatDubai(iso){ try{ if(!iso) return ''; return new Date(iso).toLocaleString('en-GB',{timeZone:'Asia/Dubai',hour12:false}); }catch(_){ return iso||''; } }
    const esc=(s='')=>String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

    function renderList(versions){
      if(!versions||!versions.length){ bodyEl.innerHTML='<p class="muted">No snapshots yet. Use “Save Draft” to create a snapshot.</p>'; return; }
      const filt=(onlyMs&&onlyMs.checked)?versions.filter(v=>v.is_milestone):versions;
      if(!filt.length){ bodyEl.innerHTML='<p class="muted">No milestones to show.</p>'; return; }
      bodyEl.innerHTML=filt.map(v=>{
        const when=v.created_at?formatDubai(v.created_at):(v.created_at_human||v.created_at||'');
        const badge=v.is_milestone?`<span class="pill" title="${esc(v.milestone_note||'Teacher-marked milestone')}" style="margin-left:8px;background:#fff4e5;border-color:#ffe7ba;color:#8a5a00;">⭐ Milestone</span>`:'';
        const snippet=(v.body_plain||'').slice(0,240);
        return `<div class="hist-row">
                  <div>
                    <div class="hist-meta"><strong>#${v.id}</strong> · ${when} ${badge}</div>
                    <div class="hist-snippet">${esc(snippet)}</div>
                  </div>
                  <div style="display:flex; gap:8px;"><button class="btn sm" data-restore="${v.id}">Restore</button></div>
                </div>`;
      }).join('');
    }

    async function openHistory(){
      backDrop.style.display='flex'; bodyEl.innerHTML='<p class="muted">Loading…</p>';
      try{
        if(!cache){ const res=await fetch(historyUrl,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}}); if(!res.ok) throw new Error('HTTP '+res.status); const data=await res.json(); cache=data.versions||[]; }
        renderList(cache);
      }catch(e){ console.error(e); bodyEl.innerHTML='<p class="muted" style="color:#b00;">Failed to load history.</p>'; }
    }

    function htmlFromPlain(text=''){ return String(text||'').replace(/\n/g,'<br>'); }

    async function restoreVersion(id){
      try{
        const url=`/workspace/{{ $type }}/restore/${id}{{ $studentIdParam ? ('?student='.$studentIdParam) : '' }}`;
        const res=await fetch(url,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
        if(!res.ok) throw new Error('HTTP '+res.status);
        const data=await res.json().catch(()=>({}));

        const html = (data.body_html ?? '').trim();
        const plain = (data.body_plain ?? '').trim();

        if (window.TIPTAP) {
          if (html) window.TIPTAP.commands.setContent(html, false);
          else window.TIPTAP.commands.setContent(htmlFromPlain(plain), false);
        }

        if (editorPlain) editorPlain.value = plain || (function(h){ const tmp=document.createElement('div'); tmp.innerHTML=h||''; tmp.querySelectorAll('br').forEach(br=>br.replaceWith('\n')); return (tmp.textContent||tmp.innerText||'').replace(/\u00A0/g,' '); })(html||'');
        if (editorHtml)  editorHtml.value  = html || htmlFromPlain(plain);

        backDrop.style.display='none';
        if (editorHtml) editorHtml.dispatchEvent(new Event('input', { bubbles:true }));
      }catch(e){ console.error(e); alert('Could not restore this version.'); }
    }

    bodyEl.addEventListener('click',(e)=>{ const t=e.target; if(t.matches('[data-restore]')){ const id=t.getAttribute('data-restore'); if(!id) return; if(confirm('Restore this snapshot into the editor?')) restoreVersion(id); }});
    if(onlyMs){ onlyMs.addEventListener('change',()=>{ if(cache) renderList(cache); }); }
    btn.addEventListener('click',openHistory);
    closeBtn.addEventListener('click',()=> backDrop.style.display='none');
    backDrop.addEventListener('click',(e)=>{ if(e.target===backDrop) backDrop.style.display='none'; });
  })();
})();  <!-- single close for the outer IIFE -->
</script>

<!-- TipTap (ESM) -->
<script type="module">
  import { Editor }      from 'https://esm.sh/@tiptap/core@2.6.6';
  import StarterKit      from 'https://esm.sh/@tiptap/starter-kit@2.6.6';
  import Underline       from 'https://esm.sh/@tiptap/extension-underline@2.6.6';
  import Link            from 'https://esm.sh/@tiptap/extension-link@2.6.6';
  import Image           from 'https://esm.sh/@tiptap/extension-image@2.6.6';
  import Placeholder     from 'https://esm.sh/@tiptap/extension-placeholder@2.6.6';

  const mount      = document.getElementById('rt-editor');
  const hidden     = document.getElementById('editor');       // plain text mirror
  const hiddenHtml = document.getElementById('body_html');    // HTML mirror (single source)
  const initialPlain = hidden?.value || '';
  const initialHtml  = (hiddenHtml?.value || '').trim();
  const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  const ownerType = "{{ $type }}";
  const ownerId   = {{ $submission->id }};

  let ed;
  try {
    ed = new Editor({
      element: mount,
      extensions: [
        StarterKit,
        Underline,
        Image.configure({ HTMLAttributes: { decoding: 'async', loading: 'lazy' } }),
        Link.configure({
          autolink: true,
          openOnClick: true,
          validate: href => /^(https?:\/\/|mailto:|tel:)/i.test(href || ''),
        }),
        Placeholder.configure({ placeholder: 'Start writing your draft…' }),
      ],
      content: (initialHtml !== '' ? initialHtml : initialPlain.replace(/\n/g, '<br>')),
      onUpdate({ editor }) {
        const plain = editor.getText('\n').trimEnd();
        const html  = editor.getHTML();
        if (hidden)     hidden.value = plain;
        if (hiddenHtml) { hiddenHtml.value = html; hiddenHtml.dispatchEvent(new Event('input', { bubbles:true })); }
        reflectActive();
      },
      onSelectionUpdate: reflectActive,
    });
  } catch (e) {
    console.error('TipTap init failed:', e);
    if (hidden) hidden.classList.remove('force-hidden');
    if (mount)  mount.setAttribute('contenteditable', 'false');
  }

  try { if (ed && hiddenHtml && !hiddenHtml.value) hiddenHtml.value = ed.getHTML(); } catch(_) {}

  window.TIPTAP = ed;

  /* ---- Toolbar wiring ---- */
  const tb = document.getElementById('tt-toolbar');
  const selHeading = document.getElementById('tt-heading');
  const linkBtn = document.getElementById('tt-link');
  const imgBtn  = document.getElementById('tt-image');
  const imgInput= document.getElementById('tt-image-input');

  function reflectActive(){
    if (!ed || ed.isDestroyed) return;
    tb.querySelectorAll('[data-cmd]').forEach(btn => {
      const cmd = btn.getAttribute('data-cmd');
      let active = false;
      try {
        switch (cmd) {
          case 'toggleBold':        active = ed.isActive('bold'); break;
          case 'toggleItalic':      active = ed.isActive('italic'); break;
          case 'toggleUnderline':   active = ed.isActive('underline'); break;
          case 'toggleStrike':      active = ed.isActive('strike'); break;
          case 'toggleBulletList':  active = ed.isActive('bulletList'); break;
          case 'toggleOrderedList': active = ed.isActive('orderedList'); break;
          case 'toggleBlockquote':  active = ed.isActive('blockquote'); break;
          case 'toggleCode':        active = ed.isActive('code'); break;
        }
      } catch(_) {}
      btn.classList.toggle('is-active', !!active);
    });
    if (ed.isActive('heading', { level: 1 })) selHeading.value = 'h1';
    else if (ed.isActive('heading', { level: 2 })) selHeading.value = 'h2';
    else if (ed.isActive('heading', { level: 3 })) selHeading.value = 'h3';
    else selHeading.value = 'p';
  }

  tb.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-cmd]');
    if (!btn) return;
    const cmd = btn.getAttribute('data-cmd');
    const chain = ed.chain().focus();
    switch (cmd) {
      case 'toggleBold':        chain.toggleBold().run(); break;
      case 'toggleItalic':      chain.toggleItalic().run(); break;
      case 'toggleUnderline':   chain.toggleUnderline().run(); break;
      case 'toggleStrike':      chain.toggleStrike().run(); break;
      case 'toggleBulletList':  chain.toggleBulletList().run(); break;
      case 'toggleOrderedList': chain.toggleOrderedList().run(); break;
      case 'toggleBlockquote':  chain.toggleBlockquote().run(); break;
      case 'toggleCode':        chain.toggleCode().run(); break;
      case 'undo':              chain.undo().run(); break;
      case 'redo':              chain.redo().run(); break;
    }
    reflectActive();
  });

  selHeading.addEventListener('change', () => {
    const v = selHeading.value;
    const ch = ed.chain().focus();
    if (v === 'p') ch.setParagraph().run();
    else if (v === 'h1') ch.toggleHeading({ level: 1 }).run();
    else if (v === 'h2') ch.toggleHeading({ level: 2 }).run();
    else if (v === 'h3') ch.toggleHeading({ level: 3 }).run();
    reflectActive();
  });

  linkBtn.addEventListener('click', () => {
    const prev = ed.getAttributes('link')?.href || '';
    const url = prompt('Enter URL (http(s)://, mailto:, or tel:)', prev);
    if (url === null) return;
    if (!/^(https?:\/\/|mailto:|tel:)/i.test(url)) { alert('Invalid URL. Must start with http://, https://, mailto:, or tel:'); return; }
    if (url === '') ed.chain().focus().unsetLink().run();
    else ed.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
  });

  imgBtn.addEventListener('click', () => imgInput.click());
  imgInput.addEventListener('change', async () => {
    const f = imgInput.files?.[0]; if (!f) return;
    await uploadAndInsert(f);
    imgInput.value = '';
  });

  mount.addEventListener('paste', async (e) => {
    const items = Array.from(e.clipboardData?.items || []);
    const file = items.map(i => i.getAsFile && i.getAsFile()).find(Boolean);
    if (file && file.type?.startsWith('image/')) {
      e.preventDefault();
      await uploadAndInsert(file);
    }
  });
  mount.addEventListener('drop', async (e) => {
    const file = e.dataTransfer?.files?.[0];
    if (file && file.type?.startsWith('image/')) {
      e.preventDefault();
      await uploadAndInsert(file);
    }
  });

  async function uploadAndInsert(file){
    try{
      const fd = new FormData();
      fd.append('file', file);
      fd.append('owner_type', "{{ $type }}");
      fd.append('owner_id', {{ $submission->id }});
      const res = await fetch('/api/tok/uploads/images', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf },
        body: fd,
        credentials: 'same-origin',
      });
      if (!res.ok) throw new Error('HTTP '+res.status);
      const data = await res.json();
      if (!data || !data.url) throw new Error('No URL returned');
      ed.chain().focus().setImage({ src: data.url, alt: file.name }).run();
    }catch(err){
      console.error(err);
      alert('Could not upload image.');
    }
  }

  reflectActive();
  document.getElementById('rt-editor').style.background = '#fff';
</script>
</body>
</html>