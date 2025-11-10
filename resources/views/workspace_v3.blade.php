{{-- Workspace V3 — Minimal skeleton with CDN TipTap (temporary) --}}
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Workspace V3 — {{ isset($type) ? ucfirst($type) : 'Workspace' }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root { --b:#eaeaea; --bg:#fff; --pane:#fafafa; }
    html,body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background:var(--bg); }

    .topbar,.bottombar {
      display:flex; justify-content:space-between; align-items:center;
      padding:12px 16px; border-bottom:1px solid var(--b); background:#fff;
    }
    .topbar { gap:12px; }
    .topbar > div:last-child { display:flex; align-items:center; gap:8px; }
    .bottombar { border-top:1px solid var(--b); border-bottom:none; }

    .main { display:grid; grid-template-columns: 1fr 360px; min-height: calc(100dvh - 96px); }
    .left { border-right:1px solid var(--b); background:#fff; min-width:0; }
    .right { background:var(--pane); overflow:auto; }
    .editor {
  min-height: calc(100vh - 120px);  /* keep content clear of the fixed footer */
  padding: 16px;
  position: relative;
}

    .editor .ProseMirror {
      min-height: 60vh;
      outline: none;
      cursor: text;
      user-select: text;
      -webkit-user-select: text;
    }
    .editor .ProseMirror:focus { outline: none; }

    .btn {
      appearance:none; border:1px solid var(--b); background:#fff;
      padding:8px 12px; border-radius:10px; cursor:pointer;
      line-height:1; white-space:nowrap;
    }
    .brand { font-weight:700; }

    .bottombar{
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
  border-top: 1px solid var(--b);
  padding: 10px 16px;
  z-index: 200;
}

    .thread-card { border:1px solid var(--b); background:#fff; border-radius:10px; padding:10px; margin-bottom:10px; cursor:pointer; }
    .thread-card:hover { box-shadow:0 0 0 2px #eef; }
    .thread-card.active { outline:2px solid #a7c4ff; }
    .pill { font-size:12px; padding:2px 8px; border:1px solid #e5e7eb; border-radius:999px; background:#f8fafc; }
    .pill-bad { color:#b91c1c; }
    #wk3-thread-detail { padding:12px; }
    .td-empty { opacity:.7; font-style:italic; padding:8px 12px; border:1px dashed var(--b); border-radius:8px; background:#fff; }
    .td-head { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; }
    .td-msg  { border-top:1px dashed #e5e5e5; padding-top:10px; margin-top:10px; background:#fff; border-radius:8px; }
    .td-msg .who { font-weight:600; }
    .td-meta { font-size:12px; color:#777; }
    .sel { margin:8px 0; padding:6px 8px; background:#fffbea; border:1px solid #f6e5a8; border-radius:8px; }

    #wk3-reply { display:none; padding:0 12px 12px; border-top:1px solid var(--b); background:#fafafa; position:sticky; bottom:0; }
    #wk3-reply textarea { width:100%; min-height:70px; resize:vertical; padding:10px; border:1px solid var(--b); border-radius:10px; font:inherit; background:#fff; }
    #wk3-reply .row { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:8px; }
    #wk3-reply small { color:#777; }
    
  </style>
</head>
<body>
  <header class="topbar" role="banner">
    <div>
      <strong class="brand">ToK V2</strong>
      <span style="margin:0 8px; color:#bbb;">&bull;</span>
      <span>{{ isset($type) ? ucfirst($type) : 'Submission' }}</span>
    </div>

@if (($role ?? 'student') === 'teacher' || ($role ?? '') === 'admin')
  <button id="wk3-btn-save" class="btn" type="button" title="Create a read-only version snapshot">Snapshot</button>
  {{-- <button id="wk3-btn-export" class="btn" type="button">Export</button> --}}
  <button id="wk3-btn-history" class="btn" type="button">History</button>
  <button id="wk3-btn-open-thread" class="btn" type="button">Open Thread</button>
@else

      <div>
        <button class="btn" id="wk3-btn-request" type="button">Request Feedback</button>
        <button class="btn" id="wk3-btn-resources" type="button">ToK Resources</button>
        <button class="btn" id="wk3-btn-logout" type="button">Logout</button>
      </div>
    @endif
  </header>

  <main class="main">
    <section class="left">
      <div id="wk3-editor"
           class="editor"
           data-doc-id="{{ $docId ?? '' }}"
           data-rev="{{ (int)($submission->working_rev ?? 0) }}"
           data-submission-id="{{ optional($submission)->id ?? '' }}">
          <script id="wk3-init-html" type="text/plain">{!! $initialHtml !!}</script>
      </div>
    </section>

    <aside class="right" aria-label="Feedback Threads">
      <div style="padding:12px 16px; border-bottom:1px solid var(--b); display:flex; align-items:center; justify-content:space-between; gap:8px;">
        <h2 style="margin:0; font-size:16px;">Threads</h2>
        <button class="btn" type="button" onclick="location.reload()">Refresh</button>
      </div>

      <div id="wk3-thread-detail">
        <div class="td-empty">Select a thread to view messages here.</div>
      </div>

      <div id="wk3-reply">
        <textarea id="wk3-reply-input" placeholder="Write a reply..."></textarea>
        <div class="row">
          <small id="wk3-reply-status">Ready</small>
          <button class="btn" id="wk3-reply-send" type="button">Send Reply</button>
        </div>
      </div>

      @if (!isset($threads) || $threads->isEmpty())
        <div style="padding:12px 8px;">
          <div class="td-empty">No threads yet.</div>
        </div>
      @else
        <div id="wk3-thread-list" style="padding:12px;">
          @foreach ($threads as $t)
            @php
              $ownerId      = optional(optional($t->version)->submission)->student_id;
              $lastAuthorId = optional($t->latestMessage)->author_id;
              $label = $t->is_resolved
                ? 'Resolved'
                : (($lastAuthorId && $ownerId && (int)$lastAuthorId === (int)$ownerId) ? 'Awaiting Teacher' : 'Awaiting Student');

              $latestBody = trim((string) optional($t->latestMessage)->body);
              $latestBody = $latestBody === '' ? '—' : mb_strimwidth($latestBody, 0, 140, '…');

              $pmFrom = is_numeric($t->pm_from ?? null) ? (int)$t->pm_from : null;
              $pmTo   = is_numeric($t->pm_to   ?? null) ? (int)$t->pm_to   : null;
            @endphp

            <div class="thread-card"
                 data-thread-id="{{ $t->id }}"
                 @if($pmFrom !== null && $pmTo !== null)
                   data-from="{{ $pmFrom }}" data-to="{{ $pmTo }}"
                 @endif>
              <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                <div>
                  <strong>#{{ $t->id }}</strong>
                  <span style="color:#777;"> - {{ optional($t->created_at)->format('Y-m-d H:i') }}</span>
                </div>
                <span class="pill">{{ $label }}</span>
              </div>

              @if (!empty($t->selection_text))
                <div class="sel"><em>&ldquo;{{ e($t->selection_text) }}&rdquo;</em></div>
              @endif

              <div style="margin-top:6px; color:#444;">{!! e($latestBody, false) !!}</div>
            </div>
          @endforeach
        </div>
      @endif
    </aside>
  </main>

  <footer class="bottombar" role="contentinfo">
    <div class="bb-left">
      @if (($role ?? 'student') === 'teacher' || ($role ?? '') === 'admin')
    <button id="wk3-btn-snapshot" class="btn" type="button">Snapshot</button>
    <button id="wk3-btn-export"   class="btn" type="button">Export</button>
    <button id="wk3-btn-history"  class="btn" type="button">History</button>
    <button id="wk3-btn-open-thread" class="btn" type="button">Open Thread</button>
    @else
        <button id="wk3-btn-save" class="btn" type="button">Save Draft</button>
        <button id="wk3-btn-history" class="btn" type="button">History</button>
        <button id="wk3-btn-request" class="btn" type="button">Request Feedback</button>
        <button id="wk3-btn-open-thread" class="btn" type="button">Open Thread</button>
      @endif
    </div>
    <div class="bb-right">
  <span id="wk3-status-label">Idle</span>
  <span style="margin:0 8px; color:#bbb;">&bull;</span>
  <small id="wk3-saved-at">Saved: &mdash;</small>
  <span style="margin:0 8px; color:#bbb;">&bull;</span>
  <small id="wk3-sync-pill">In sync</small>

  <div id="wk3-toast"
       style="display:none; margin-left:12px; background:#fff7cc; border:1px solid #f2e38b; padding:4px 8px; border-radius:8px; font-size:12px;">
    Out of date. <button id="wk3-toast-reload" class="btn" style="padding:2px 6px; font-size:12px;">Reload</button>
  </div>

  <!-- ✅ success toast (hidden by default) -->
  <div id="wk3-toast-success"
       style="display:none; margin-left:12px; background:#e6ffed; border:1px solid #8be39c; padding:4px 8px; border-radius:8px; font-size:12px; color:#0f5132;">
    Restored successfully.
  </div>
</div>
  </footer>

<!-- History Overlay (hidden by default) -->
<div id="wk3-history" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:9999; align-items:center; justify-content:center;">
  <div style="background:#fff; width:min(780px, 92vw); max-height:80vh; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,.08);">
    <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border-bottom:1px solid #eee;">
      <strong>Version History</strong>
      <button id="wk3-history-close" class="btn" type="button">Close</button>
    </div>
    <div id="wk3-history-body" style="padding:10px 14px; max-height:65vh; overflow:auto;">
      <div style="opacity:.7; font-style:italic;">Loading…</div>
    </div>
  </div>
</div>

  <!-- TEMP: CDN TipTap -->
  <script type="module">
    import { Editor } from "https://esm.sh/@tiptap/core@2"
    import StarterKit from "https://esm.sh/@tiptap/starter-kit@2"

    // Elements & constants
    const el       = document.getElementById('wk3-editor')
    const TYPE     = "{{ $type ?? 'exhibition' }}"
    const CSRF     = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const $status  = document.getElementById('wk3-status-label')
    const $savedAt = document.getElementById('wk3-saved-at')
    const $list    = document.getElementById('wk3-thread-list')
    const $detail  = document.getElementById('wk3-thread-detail')
    const $sync    = document.getElementById('wk3-sync-pill')
    const $toast   = document.getElementById('wk3-toast')
    const $toastBtn = document.getElementById('wk3-toast-reload')
    $toastBtn?.addEventListener('click', () => location.reload());

    const SUBMISSION = el?.dataset.submissionId || ""

    // Reply UI
    const $replyBox   = document.getElementById('wk3-reply')
    const $replyInput = document.getElementById('wk3-reply-input')
    const $replyBtn   = document.getElementById('wk3-reply-send')
    const $replyStat  = document.getElementById('wk3-reply-status')
    let selectedThreadId = null

    const setStatus    = t => { if ($status)  $status.textContent  = t }
    const setSavedAt   = d => { if ($savedAt) $savedAt.textContent = `Saved: ${d}` }
    const setReplyStat = t => { if ($replyStat) $replyStat.textContent = t }
    const setSync = t => {
      if (!$sync) return;
      $sync.textContent = t;
      if (t.toLowerCase().includes('out of date') || t.toLowerCase().includes('conflict')) {
        $sync.classList.add('pill-bad');
      } else {
        $sync.classList.remove('pill-bad');
      }
    };

    // Toast helpers
    function showToast(){ if ($toast) $toast.style.display = 'inline-flex' }
    function hideToast(){ if ($toast) $toast.style.display = 'none' }

    // --- client revision state (seeded from server) ---
    const initialRev = parseInt(el?.dataset.rev || '0', 10)
    let rev = Number.isFinite(initialRev) ? initialRev : 0

    // Mount TipTap
    let editor = null
    if (!el) {
      console.error('[wk3] Editor mount element not found.')
    } else {
      editor = new Editor({
        element: el,
        extensions: [StarterKit],
        content: (document.getElementById('wk3-init-html')?.textContent || '').trim() || '<p></p>',
        editable: true,
        autofocus: 'start',
      })
      queueMicrotask(() => el.querySelector('.ProseMirror')?.focus())
      // @ts-ignore
      window.wk3 = { editor, get rev(){ return rev } }
    }

    // -------------------------
    // AUTOSAVE + STATUS UX
    // -------------------------
    const AUTOSAVE_DELAY = 1200; // ms
    let autosaveTimer = null;
    let saving = false;
    let queued = false;

    const TYPING  = 'Typing...';
    const SAVING  = 'Saving...';
    const IDLE    = 'Idle';

    function scheduleAutosave() {
      if (autosaveTimer) clearTimeout(autosaveTimer);
      autosaveTimer = setTimeout(() => {
        setStatus(SAVING);
        setSync('Saving');
        saveDraft(false);
      }, AUTOSAVE_DELAY);
    }

    if (editor) {
      editor.on('transaction', ({ transaction }) => {
        if (!transaction.docChanged) return;
        if ($status && $status.textContent !== SAVING) setStatus(TYPING);
        setSync('Unsaved changes');
        scheduleAutosave();
      });
      editor.on('blur', () => { if (!autosaveTimer && !saving) setStatus(IDLE); });
    }

    // Cmd/Ctrl+S saves immediately
    document.addEventListener('keydown', (e) => {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's') {
        e.preventDefault();
        if (autosaveTimer) clearTimeout(autosaveTimer);
        setStatus(SAVING);
        setSync('Saving');
        saveDraft(false);
      }
    });

    // -------------------------
    // SAVE (serialized + 409 safe)
    // -------------------------
    async function saveDraft(isSnapshot = false) {
      if (!editor) return;
      if (saving) { queued = true; return; }

      saving = true;
      setStatus('Saving...');

      const payload = {
        autosave: true,
        submission_id: SUBMISSION ? parseInt(SUBMISSION, 10) : undefined,
        body: editor.getText(),
        body_html: editor.getHTML(),
        rev,
        snapshot: !!isSnapshot,
      };

      try {
        const url = SUBMISSION
          ? `/workspace/${TYPE}/save?submission_id=${encodeURIComponent(SUBMISSION)}`
          : `/workspace/${TYPE}/save`;

        const res = await fetch(url, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(payload),
        });

        if (res.status === 409) {
          let expected = null;
          try { const j = await res.json(); expected = (typeof j?.expected === 'number') ? j.expected : null; } catch {}
          if (typeof expected === 'number') rev = expected;
          clearTimeout(autosaveTimer);
          autosaveTimer = setTimeout(() => saveDraft(false), AUTOSAVE_DELAY);
          setStatus('Conflict');
          setSync('Out of date');
          showToast();
          return;
        }

        if (!res.ok) {
          setStatus(`Error (${res.status})`);
          setSync('Error');
          console.error('[wk3] save failed', res.status, await res.text().catch(() => ''));
          return;
        }

        try {
          const j = await res.json();
          if (typeof j?.rev === 'number') rev = j.rev; else rev += 1;
        } catch { rev += 1; }

        setStatus('Saved');
        setSavedAt(new Date().toLocaleTimeString());
        setSync('In sync');
        hideToast();

      } catch (e) {
        console.error('[wk3] save error', e);
        setStatus('Error (network)');
        setSync('Error');

      } finally {
        saving = false;
        if (queued) { queued = false; saveDraft(false); }
      }
    }
    window.saveDraft = saveDraft;

    // -------------------------
    // THREAD LIST / DETAIL
    // -------------------------
    function renderDetailLoading(id) {
      $detail.innerHTML = `<div class="td-head"><strong>Thread #${id}</strong><span class="td-meta">Loading…</span></div>`
    }
    function renderDetailEmpty() {
      $detail.innerHTML = `<div class="td-empty">Select a thread to view messages here.</div>`
      if ($replyBox) $replyBox.style.display = 'none'
      selectedThreadId = null
    }
    function escapeHtml(s){ return (s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])) }
    function renderDetail(thread) {
      const head = `
        <div class="td-head">
          <div>
            <strong>Thread #${thread.id}</strong>
            ${thread.label ? `<span class="pill" style="margin-left:8px;">${thread.label}</span>` : ``}
          </div>
          <span class="td-meta">${thread.created_at || ''}</span>
        </div>
      `
      const sel = thread.selection_text
        ? `<div class="sel"><em>&ldquo;${escapeHtml(thread.selection_text)}&rdquo;</em></div>`
        : ``

      const msgs = (thread.messages || []).map(m => `
        <div class="td-msg">
          <div class="who">${escapeHtml(m.author?.name || 'User')}</div>
          <div class="td-meta">${m.created_at || ''}</div>
          <div style="margin-top:6px; white-space:pre-wrap;">${escapeHtml(m.body || '')}</div>
        </div>
      `).join('')

      $detail.innerHTML = head + sel + (msgs || `<div class="td-empty" style="margin-top:6px;">No messages.</div>`)
      if ($replyBox) $replyBox.style.display = 'block'
      if ($replyInput) $replyInput.focus()
    }

    if ($list) {
      $list.addEventListener('click', async (ev) => {
        const card = ev.target.closest('.thread-card')
        if (!card) return

        $list.querySelectorAll('.thread-card.active').forEach(el => el.classList.remove('active'))
        card.classList.add('active')

        const id = parseInt(card.dataset.threadId || '0', 10)
        if (!id) return
        selectedThreadId = id

        const from = parseInt(card.dataset.from || '', 10)
        const to   = parseInt(card.dataset.to   || '', 10)
        if (editor && Number.isFinite(from) && Number.isFinite(to) && to > from) {
          try {
            editor.chain().setTextSelection({ from, to }).run()
            editor.view.scrollIntoView()
          } catch (e) { console.warn('[wk3] setTextSelection failed', e) }
        }

        try {
          renderDetailLoading(id)
          const res = await fetch(`/workspace/${TYPE}/thread/${id}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
          })
          if (!res.ok) {
            $detail.innerHTML = `<div class="td-empty">Couldn’t load thread (#${id}).</div>`
            if ($replyBox) $replyBox.style.display = 'none'
            console.error('[wk3] thread load failed', res.status, await res.text().catch(()=>'')); 
            return
          }
          const data = await res.json().catch(() => null)
          renderDetail(data || { id })
        } catch (e) {
          $detail.innerHTML = `<div class="td-empty">Network error loading thread (#${id}).</div>`
          if ($replyBox) $replyBox.style.display = 'none'
          console.error('[wk3] thread load error', e)
        }
      })
    }

    async function openThreadFromSelection() {
      if (!editor) return
      if (!SUBMISSION) { alert('Missing submission id. Reload this page.'); return }

      const sel  = editor.state.selection
      const from = sel?.from ?? 0
      const to   = sel?.to ?? 0
      if (!Number.isFinite(from) || !Number.isFinite(to) || to <= from) { alert('Select some text first.'); return }

      let selectionText = ''
      try { selectionText = editor.state.doc.textBetween(from, to, ' ') } catch {}

      const body = prompt('First message for this thread:', '')
      if (body === null) return
      const trimmed = (body || '').trim()
      if (!trimmed) { alert('Message can’t be empty.'); return }

      try {
        const url = `/workspace/${TYPE}/thread?submission_id=${encodeURIComponent(SUBMISSION)}`
        const payload = {
          submission_id: parseInt(SUBMISSION, 10),
          selection_text: selectionText.slice(0, 255),
          body: trimmed,
          start_offset: from,
          end_offset: to,
        }
        const res = await fetch(url, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(payload),
        })

        if (!res.ok) {
          let msg = ''
          try { msg = (await res.json()).message || '' } catch { msg = await res.text().catch(()=> '') }
          console.error('[wk3] open thread failed', res.status, msg)
          alert(`Could not open the thread (HTTP ${res.status})${msg ? `\n${msg}` : ''}.`)
          return
        }

        location.reload()
      } catch (e) {
        console.error('[wk3] open thread error', e)
        alert('Network error while opening the thread.')
      }
    }

    async function sendReply() {
      if (!selectedThreadId) return
      const body = ($replyInput?.value || '').trim()
      if (!body) { setReplyStat('Type a message first.'); return }

      setReplyStat('Sending…')

      try {
        const res = await fetch(`/workspace/${TYPE}/thread/${selectedThreadId}/reply`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({ body }),
        })

        const contentType = res.headers.get('Content-Type') || ''
        if (res.ok && contentType.includes('application/json')) {
          const data = await res.json().catch(() => ({}))
          if (data && data.thread) {
            renderDetail(data.thread)
          } else {
            const again = await fetch(`/workspace/${TYPE}/thread/${selectedThreadId}`, {
              headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
              credentials: 'same-origin',
            })
            const j = await again.json().catch(()=>null)
            if (j) renderDetail(j)
          }
        } else if (res.ok) {
          const again = await fetch(`/workspace/${TYPE}/thread/${selectedThreadId}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
          })
          const j = await again.json().catch(()=>null)
          if (j) renderDetail(j)
        } else {
          console.error('[wk3] reply failed', res.status, await res.text().catch(()=>'')); 
          setReplyStat(`Error (${res.status})`)
          return
        }

        if ($replyInput) $replyInput.value = ''
        setReplyStat('Sent')
        setTimeout(() => setReplyStat('Ready'), 1200)
      } catch (e) {
        console.error('[wk3] reply error', e)
        setReplyStat('Network error')
      }
    }

    // Wire buttons
    document.getElementById('wk3-btn-save')?.addEventListener('click', () => {
      // manual save = snapshot
      saveDraft(true);
    });
    
    document.getElementById('wk3-btn-export')?.addEventListener('click', () => {
  const type = "{{ $type ?? 'essay' }}";
  const role = "{{ $role ?? 'student' }}";
  // use the current submission’s student id if available; otherwise fall back to any ?student in the URL
  const studentId = "{{ optional($submission)->student_id ?? '' }}" || new URLSearchParams(location.search).get('student') || '';

  if ((role === 'teacher' || role === 'admin') && !studentId) {
    alert('No student is associated with this page. Open a student’s workspace to export.');
    return;
  }

  const url = studentId
    ? `/workspace/${type}/export?student=${studentId}`
    : `/workspace/${type}/export`; // students can self-export without param

  // open in SAME TAB to preserve the session
  window.location.href = url;
});

    document.getElementById('wk3-btn-request')?.addEventListener('click', () => console.debug('[wk3] Request Feedback (stub)'))
    document.getElementById('wk3-btn-resources')?.addEventListener('click', () => {
      window.open('/resources', '_blank');
    });
    document.getElementById('wk3-btn-dashboard')?.addEventListener('click', () => {
      const DASH_URL = "{{ (($role ?? 'student') === 'student') ? '/student/dashboard' : '/students' }}";
      window.open(DASH_URL, '_self');
    });
    document.getElementById('wk3-btn-open-thread')?.addEventListener('click', openThreadFromSelection)
    $replyBtn?.addEventListener('click', sendReply)
    
    // Toggle thread pane visibility (safe cosmetic feature)
    document.getElementById('wk3-btn-toggle-threads')?.addEventListener('click', () => {
        const rightPane = document.querySelector('.right');
        const leftPane = document.querySelector('.left');
        const btn = document.getElementById('wk3-btn-toggle-threads');

  if (!rightPane || !leftPane || !btn) return;

  const isHidden = rightPane.style.display === 'none';
  if (isHidden) {
    rightPane.style.display = 'block';
    leftPane.style.gridColumn = '1 / span 1';
    btn.textContent = 'Hide threads';
  } else {
    rightPane.style.display = 'none';
    leftPane.style.gridColumn = '1 / -1';
    btn.textContent = 'Show threads';
  }
});

    // --- History overlay logic ---
    const $history = document.getElementById('wk3-history');
    const $historyBody = document.getElementById('wk3-history-body');
    const $historyClose = document.getElementById('wk3-history-close');

    document.getElementById('wk3-btn-history')?.addEventListener('click', async () => {
      if (!$history) return;
      $history.style.display = 'flex';
      $historyBody.innerHTML = '<div style="opacity:.7; font-style:italic;">Loading…</div>';

      try {
        const res = await fetch(`/workspace/${TYPE}/history`, {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const payload = await res.json().catch(() => ({ versions: [] }));
        const data = Array.isArray(payload.versions) ? payload.versions : [];
        if (data.length === 0) {
          $historyBody.innerHTML = '<div style="opacity:.7; font-style:italic;">No previous versions.</div>';
          return;
        }

        $historyBody.innerHTML = data.map(v => `
          <div style="border-bottom:1px solid #eee; padding:8px 0;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
              <div>
                <strong>#${v.id}</strong> – ${v.created_at_human || v.created_at || ''}
                <div style="font-size:13px; color:#555; margin-top:4px;">${v.summary || '(no summary)'}</div>
              </div>
              <button class="btn wk3-restore" data-version="${v.id}" type="button">Restore</button>
              <button class="btn wk3-compare" data-version="${v.id}" type="button">Compare</button>
            </div>
          </div>
        `).join('');

      } catch (err) {
        console.error('[wk3] history load failed', err);
        $historyBody.innerHTML = '<div style="color:#b00;">Error loading history.</div>';
      }

      $historyBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.wk3-compare');
        if (!btn) return;
        const ver = parseInt(btn.dataset.version || '0', 10);
        if (!ver) return;
        location.href = `/workspace/${TYPE}/compare/${ver}`;
      });
    });

    $historyBody?.addEventListener('click', async (e) => {
  const btn = e.target.closest('.wk3-restore');
  if (!btn) return;
  const ver = parseInt(btn.dataset.version || '0', 10);
  if (!ver) return;

  btn.disabled = true;
  btn.textContent = 'Restoring…';

  try {
    const res = await fetch(`/workspace/${TYPE}/restore/${ver}`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF,
        'X-Requested-With': 'XMLHttpRequest',
      }
    });

    if (res.ok) {
      // redirect with success flag
      window.location.href = `/workspace/${TYPE}?restored=1`;
    } else {
      console.error('[wk3] restore failed', res.status);
      $historyBody.innerHTML = `<div style="color:#b00;">Restore failed (HTTP ${res.status}).</div>`;
    }
  } catch (err) {
    console.error('[wk3] restore error', err);
    $historyBody.innerHTML = `<div style="color:#b00;">Network error restoring version.</div>`;
  }
});


    $historyClose?.addEventListener('click', () => {
      if ($history) $history.style.display = 'none';
    });

    // Logout handler (POST /logout + redirect)
    document.getElementById('wk3-btn-logout')?.addEventListener('click', async () => {
      try {
        await fetch('/logout', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
        });
      } catch (err) {
        console.error('Logout failed:', err);
      } finally {
        window.location.href = '/login';
      }
    });
// --- Success toast helpers + ?restored=1 detection ---
const $successToast = document.getElementById('wk3-toast-success');

function showSuccessToast() {
  if ($successToast) {
    $successToast.style.display = 'inline-flex';
    setTimeout(() => {
      $successToast.style.display = 'none';
    }, 3000); // hide after 3 s
  }
}

(function checkRestoredParam() {
  const params = new URLSearchParams(window.location.search);
  if (params.get('restored') === '1') {
    showSuccessToast();
    params.delete('restored');
    const newUrl =
      window.location.pathname +
      (params.toString() ? '?' + params.toString() : '');
    window.history.replaceState({}, '', newUrl);
  }
})();
    // initial pill state
    setSync('In sync');
  </script>
</body>
</html>