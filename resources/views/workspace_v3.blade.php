<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Workspace V3 — {{ isset($type) ? ucfirst($type) : 'Workspace' }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="{{ asset('tok-admin/css/workspace-v3.css') }}">

</head>
<body>
<script>
  window.AUTH_ID   = {{ (int) (optional(Auth::user())->id ?? 0) }};
  window.AUTH_NAME = @json(optional(Auth::user())->name ?? 'You');

  @php
    $isStaff = in_array(optional(Auth::user())->role, ['teacher','admin'], true);
    $counterpart = $isStaff
      ? (isset($student) && isset($student->name) ? $student->name : 'Student')
      : ((isset($supervisorLabel) && $supervisorLabel !== '') ? $supervisorLabel : 'Teacher');
  @endphp
  window.COUNTERPART_NAME = @json($counterpart);
</script>

  <header class="topbar" role="banner">
    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">

      <div class="topbar-brand">
        <img
          src="{{ asset('tok-ls/ToKLoopLogo.svg') }}"
          alt="ASAD ToK Loop logo">
        <span style="margin:0 8px; color:#bbb;">&bull;</span>
        <span>{{ isset($type) ? ucfirst($type) : 'Submission' }}</span>
      </div>

      @php
        $isStudent = optional(Auth::user())->role === 'student';
      @endphp

      @if ($isStudent && !empty($supervisorLabel))
        <span class="pill" style="display:inline-flex; align-items:center; gap:4px; font-size:13px;">
          <span style="opacity:.75;">ToK Supervisor</span>
          <span style="font-weight:600;">{{ $supervisorLabel }}</span>
        </span>
      @endif

    </div>

    <div class="utils">
      <button class="btn" id="wk3-btn-messages" type="button" aria-expanded="false">
        Messages
        <span id="wk3-msg-badge" class="msg-badge" style="display:none;"></span>
      </button>
  
      <div id="msg-panel" style="display:none; position:absolute; right:16px; top:56px; width:360px; max-height:60vh; overflow:auto; background:#fff; border:1px solid #e5e5e5; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.12); z-index:9999; padding:12px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px;">
          <strong>Messages</strong>
          <button type="button" class="btn" id="msg-close" style="padding:4px 8px;">Close</button>
        </div>
        <div id="msg-list"><p class="td-empty" style="margin:6px 0;">Loading…</p></div>
        <form id="msg-form" style="margin-top:10px; display:none;">
          @csrf
          <textarea name="body" rows="3" placeholder="Type a message…" required
            style="width:100%; padding:8px; border:1px solid var(--b); border-radius:8px; background:var(--bg); color:var(--fg);"></textarea>
          <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px;">
            <button type="submit" class="btn">Send</button>
          </div>
        </form>
      </div>
  
      <button class="btn" id="wk3-btn-toggle-threads" type="button">Hide threads</button>
      <a href="{{ route('resources.index') }}" class="btn inline-flex items-center justify-center" id="wk3-btn-resources">
        ToK Resources
      </a>
      @php
        $dashRoute = match(optional(Auth::user())->role) {
          'student' => 'student.dashboard',
          'teacher' => 'students.index',
          'admin'   => 'admin.dashboard',
          default   => null,
        };
      @endphp
      @if ($dashRoute)
        @unless (request()->routeIs($dashRoute))
          <a class="btn" id="wk3-btn-dashboard" href="{{ route($dashRoute) }}">Dashboard</a>
        @endunless
      @endif
      <button class="btn" id="wk3-btn-logout" type="button">Logout</button>
    </div>
  </header>

  <main class="main">
    <section class="left">
      <div class="editor-wrap" data-theme="light">
        <div class="wk3-toolbar" aria-label="Editor toolbar">
          <button type="button" class="wk3-tool" data-cmd="undo" title="Undo">↶</button>
          <button type="button" class="wk3-tool" data-cmd="redo" title="Redo">↷</button>

          <span class="wk3-sep" aria-hidden="true"></span>

          <button type="button" class="wk3-tool" data-cmd="bold" title="Bold"><strong>B</strong></button>
          <button type="button" class="wk3-tool" data-cmd="italic" title="Italic"><em>I</em></button>
          <button type="button" class="wk3-tool" data-cmd="underline" title="Underline"><u>U</u></button>
          <button type="button" class="wk3-tool" data-cmd="strike" title="Strike">S̶</button>

          <span class="wk3-sep" aria-hidden="true"></span>

          <button type="button" class="wk3-tool" data-cmd="heading1" title="Heading 1">H1</button>
          <button type="button" class="wk3-tool" data-cmd="heading2" title="Heading 2">H2</button>
          <button type="button" class="wk3-tool" data-cmd="paragraph" title="Paragraph">¶</button>

          <span class="wk3-sep" aria-hidden="true"></span>

          <button type="button" class="wk3-tool" data-cmd="bulletList" title="Bulleted list">• List</button>
          <button type="button" class="wk3-tool" data-cmd="orderedList" title="Numbered list">1. List</button>
          <button type="button" class="wk3-tool" data-cmd="blockquote" title="Blockquote">❝</button>
          <button type="button" class="wk3-tool" data-cmd="link" title="Insert/edit link">🔗 Link</button>
          <button type="button" class="wk3-tool" data-cmd="codeBlock" title="Code">&lt;/&gt;</button>

          <span class="wk3-sep" aria-hidden="true"></span>

          <button type="button" class="wk3-tool" id="wk3-insert-image" data-cmd="insertImage" title="Insert image">Image</button>
          <input type="file" id="wk3-image-input" accept="image/*" hidden>

          <span class="wk3-sep" aria-hidden="true"></span>

          <div class="wk3-theme-toggle" id="wk3-theme-toggle" role="switch" aria-label="Toggle theme">
            <span class="sun" aria-hidden="true">🌞</span>
            <span class="moon" aria-hidden="true">🌜</span>
          </div>
        </div> 

        <div id="wk3-editor"
             class="editor"
             data-doc-id="{{ $docId ?? '' }}"
             data-rev="{{ (int)($submission->working_rev ?? 0) }}"
             data-submission-id="{{ optional($submission)->id ?? '' }}"
             data-has-context="{{ optional($submission)->id ? '1' : '0' }}">
          <script id="wk3-init-html" type="text/plain">{!! $initialHtml !!}</script>
        </div>
      </div>
    </section>

    <aside class="right" aria-label="Feedback Threads">
      <div style="padding:12px 16px; border-bottom:1px solid var(--b); display:flex; align-items:center; justify-content:space-between; gap:8px;">
        <h2 style="margin:0; font-size:16px;">Threads</h2>
      </div>

      <div id="wk3-thread-detail">
        <div class="td-empty">Select a thread to view messages here.</div>
      </div>

      <div id="wk3-reply">
        <textarea id="wk3-reply-input" placeholder="Write a reply..."></textarea>
        <div class="row">
          <button class="btn" id="wk3-reply-send" type="button">Reply</button>
          <button class="btn" id="wk3-reply-close" type="button">Close</button>
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
              // Basic ownership data
              $ownerId      = optional(optional($t->version)->submission)->student_id;
              $lastAuthorId = optional($t->latestMessage)->author_id;

              // Label logic
              if ($t->is_resolved) {
                  $label = 'Resolved';
              } elseif ($lastAuthorId && $ownerId && (int)$lastAuthorId === (int)$ownerId) {
                  $label = 'Awaiting Teacher';
              } else {
                  $label = 'Awaiting Student';
              }

              // Latest message preview
              $latestBody = trim((string) optional($t->latestMessage)->body);
              $latestBody = $latestBody === '' ? '—' : mb_strimwidth($latestBody, 0, 140, '…');

              // Highlight selection range
              $pmFrom = is_numeric($t->pm_from ?? null) ? (int)$t->pm_from : null;
              $pmTo   = is_numeric($t->pm_to   ?? null) ? (int)$t->pm_to   : null;

              // Student-request status
              $firstMessage     = $t->messages->first();
              $firstAuthorId    = optional($firstMessage)->author_id;
              $isStudentRequest = $ownerId && $firstAuthorId && ((int)$firstAuthorId === (int)$ownerId);

              // Role-aware overdue logic
              $roleVal  = strtolower((string) ($role ?? 'guest'));
              $isStaff  = in_array($roleVal, ['teacher','admin'], true);
              $overdue  = false;
              $lastAt   = optional($t->latestMessage)->created_at;

              if (!$t->is_resolved && $lastAt) {
                  if ($isStaff && $label === 'Awaiting Teacher') {
                      $overdue = $lastAt->lt(now()->subHours(48));
                  } elseif (!$isStaff && $label === 'Awaiting Student') {
                      $overdue = $lastAt->lt(now()->subDays(2));
                  }
              }

              // Build card classes
              $cardClasses = ['thread-card'];
              if ($isStudentRequest && !$t->is_resolved) {
                  $cardClasses[] = 'thread-student-request';
              }
              if ($t->is_resolved) {
                  $cardClasses[] = 'thread-resolved';
              }
              if ($overdue) {
                  $cardClasses[] = 'thread-overdue';
              }

              // Pill classes
              $statusPillClass = 'pill';
              if ($t->is_resolved) {
                  $statusPillClass .= ' pill-resolved';
              } elseif ($overdue) {
                  $statusPillClass .= ' pill-overdue';
              }
            @endphp

            <div class="{{ implode(' ', $cardClasses) }}"
                 data-thread-id="{{ $t->id }}"
                 @if($pmFrom !== null && $pmTo !== null)
                   data-from="{{ $pmFrom }}" data-to="{{ $pmTo }}"
                 @endif>

              <div class="thread-list-head" style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                <div>
                  <strong>#{{ $t->id }}</strong>
                  <span class="td-meta" style="color:#777;">
                    - {{ optional($t->created_at)->format('Y-m-d H:i') }}
                  </span>
                </div>

                <div style="display:flex; gap:6px; align-items:center;">
                  @if($isStudentRequest)
                    <span class="pill pill-student-request">Student request</span>
                  @endif
                  <span class="{{ $statusPillClass }}">{{ $label }}</span>
                </div>
              </div>

              @if (!empty($t->selection_text))
                <div class="sel"><em>&ldquo;{{ e($t->selection_text) }}&rdquo;</em></div>
              @endif

              <div style="margin-top:6px; color:#444;">{{ $latestBody }}</div>
            </div>

          @endforeach
        </div>
      @endif

    </aside>
  </main>

  <footer class="bottombar" role="contentinfo">
    <div class="bb-left">
      @php
        $role     = $role ?? 'student';
        $isStaff  = ($role === 'teacher' || $role === 'admin');

        $submissionId     = optional($submission)->id;
        $exportStudentId  = optional($submission)->student_id;
        $hasSubmission    = (bool) $submissionId;

        $hasStudentQuery  = request()->has('student') && request('student') !== '';
        $canExportLink    = $isStaff && $hasSubmission && $hasStudentQuery && $exportStudentId;
        $hasContext       = $hasSubmission;
      @endphp

      @if ($isStaff)
        <button id="wk3-btn-save" class="btn" type="button"
                title="Create a read-only version snapshot"
                {{ $hasContext ? '' : 'disabled' }}>
          Snapshot
        </button>

        <button id="wk3-btn-history" class="btn" type="button" {{ $hasContext ? '' : 'disabled' }}>
          History
        </button>

        <button id="wk3-open-thread" class="btn" type="button" {{ $hasContext ? '' : 'disabled' }}>
          Open Thread
        </button>

        @if ($canExportLink)
          <a id="wk3-btn-export"
             class="btn"
             href="/workspace/{{ $type ?? 'essay' }}/export?student={{ $exportStudentId }}"
             style="text-decoration:none;color:inherit">
            Export
          </a>
        @else
          <button class="btn" type="button" disabled title="Open a student’s workspace from Dashboard to export">
            Export
          </button>
        @endif

      @else
        <button id="wk3-btn-history" class="btn" type="button">History</button>
        <button id="wk3-btn-request" class="btn" type="button">Request Feedback</button>
      @endif
    </div>

    <div class="bb-right wk3-status">
      <span id="wk3-status-label">Idle</span>
      <span style="margin:0 8px; color:#bbb;">&bull;</span>
      <small id="wk3-saved-at">Saved: —</small>
      <span style="margin:0 8px; color:#bbb;">&bull;</span>
      <small id="wk3-sync-pill">In sync</small>

      <div id="wk3-toast"
           role="status" aria-live="polite" aria-atomic="true"
           style="display:none; margin-left:12px; background:#fff7cc; border:1px solid #f2e38b; padding:4px 8px; border-radius:8px; font-size:12px;">
        <span id="wk3-toast-text">Out of date.</span>
        <button id="wk3-toast-reload" class="btn" style="padding:2px 6px; font-size:12px; margin-left:6px;">Reload</button>
      </div>

      <div id="wk3-toast-success"
           role="status" aria-live="polite" aria-atomic="true"
           style="display:none; margin-left:12px; background:#e6ffed; border:1px solid #8be39c; padding:4px 8px; border-radius:8px; font-size:12px; color:#0f5132;">
        Restored successfully.
      </div>
    </div>
  </footer>

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

  <script type="module">
    import { Editor } from "https://esm.sh/@tiptap/core@2";
    import StarterKit from "https://esm.sh/@tiptap/starter-kit@2";
    import Image from "https://esm.sh/@tiptap/extension-image@2";
    import Link from "https://esm.sh/@tiptap/extension-link@2";
    import Underline from "https://esm.sh/@tiptap/extension-underline@2";

    // Elements & constants
    const el         = document.getElementById('wk3-editor');
    const TYPE       = "{{ $type ?? 'exhibition' }}";
    const ROLE       = "{{ $role ?? 'student' }}";
    const STUDENT_ID = {{ (int) $student->id }};
    const IS_STAFF   = (ROLE === 'teacher' || ROLE === 'admin');
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const $status    = document.getElementById('wk3-status-label');
    const $savedAt   = document.getElementById('wk3-saved-at');
    const $list      = document.getElementById('wk3-thread-list');
    const $detail    = document.getElementById('wk3-thread-detail');
    const $sync      = document.getElementById('wk3-sync-pill');
    const $toast     = document.getElementById('wk3-toast');
    const $toastText = document.getElementById('wk3-toast-text');
    const $toastBtn  = document.getElementById('wk3-toast-reload');

    $toastBtn?.addEventListener('click', () => {
      const url = new URL(location.href);
      url.searchParams.set('ts', Date.now().toString()); 
      location.replace(url.toString());
    });

    const SUBMISSION  = el?.dataset.submissionId || "";
    const HAS_CONTEXT = !!SUBMISSION;

    // Reply UI
    const $replyBox    = document.getElementById('wk3-reply');
    const $replyInput  = document.getElementById('wk3-reply-input');
    const $replyBtn    = document.getElementById('wk3-reply-send');
    const $replyClose  = document.getElementById('wk3-reply-close');
    const $replyStat   = document.getElementById('wk3-reply-status'); 
    let selectedThreadId = null;

    const setStatus    = t => { if ($status)  $status.textContent  = t; };
    const setSavedAt   = d => { if ($savedAt) $savedAt.textContent = `Saved: ${d}`; };
    const setReplyStat = t => { if ($replyStat) $replyStat.textContent = t; };
    const setSync = t => {
      if (!$sync) return;
      $sync.textContent = t;
      if (t.toLowerCase().includes('out of date') || t.toLowerCase().includes('conflict')) {
        $sync.classList.add('pill-bad');
      } else {
        $sync.classList.remove('pill-bad');
      }
    };

    function setToastMessage(msg){
      if ($toastText) $toastText.textContent = msg || 'Out of date.';
    }
    function showToast(msg){
      setToastMessage(msg);
      if ($toast) $toast.style.display = 'inline-flex';
    }
    function hideToast(){ if ($toast) $toast.style.display = 'none'; }

    async function uploadImageFile(file) {
      if (!file || !file.type.startsWith('image/')) throw new Error('Not an image');
      const fd = new FormData();
      fd.append('file', file);
      const res = await fetch(`/workspace/${TYPE}/upload`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: fd,
      });
      if (!res.ok) {
        let msg = '';
        try { msg = (await res.json()).error || ''; } catch { msg = await res.text().catch(()=> ''); }
        throw new Error(`Upload failed (HTTP ${res.status})${msg ? ` — ${msg}` : ''}`);
      }
      const j = await res.json();
      if (!j?.url) throw new Error('Upload response missing URL');
      return j.url;
    }

    function bindImageReplace(editor) {
      const pmView = editor?.view;
      if (!pmView) return;

      pmView.dom.addEventListener('dblclick', async (ev) => {
        if (!HAS_CONTEXT) return;
        const target = ev.target;
        if (!(target && target.nodeName === 'IMG')) return;

        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = async () => {
          const file = input.files?.[0];
          if (!file) return;
          try {
            setStatus('Uploading image…'); setSync('Saving');
            const url = await uploadImageFile(file);

            editor.chain()
              .setNodeSelection(editor.view.posAtDOM(target, 0))
              .updateAttributes('image', { src: url, alt: '' })
              .run();

            scheduleAutosave();
            setStatus('Saved'); setSync('In sync');
          } catch (e) {
            console.error('[wk3] image replace failed', e);
            setStatus('Image replace failed'); setSync('Error');
          }
        };
        input.click();
      });
    }

    const initialRev = parseInt(el?.dataset.rev || '0', 10);
    let rev = Number.isFinite(initialRev) ? initialRev : 0;

    let editor = null;
    if (!el) {
      console.error('[wk3] Editor mount element not found.');
    } else {
      editor = new Editor({
        element: el,
        content: (document.getElementById('wk3-init-html')?.textContent || '').trim() || '<p></p>',
        editable: HAS_CONTEXT,
        autofocus: HAS_CONTEXT ? 'start' : false,
        extensions: [
          StarterKit,
          Underline,
          Image.configure({ inline: false, allowBase64: false }),
          Link.configure({
            autolink: true,
            linkOnPaste: true,
            openOnClick: false, 
            HTMLAttributes: {
              rel: 'noopener noreferrer nofollow',
              target: '_blank'
            }
          }),
        ],
        editorProps: {
          handlePaste: (view, event) => {
            if (!HAS_CONTEXT) return false;
            const files = event?.clipboardData?.files;
            if (!files || !files.length) return false;
            const file = [...files].find(f => f.type.startsWith('image/'));
            if (!file) return false;
            event.preventDefault();
            (async () => {
              try {
                setStatus('Uploading image…'); setSync('Saving');
                const url = await uploadImageFile(file);
                editor.chain().focus().setImage({ src: url, alt: '' }).run();
                scheduleAutosave();
                setStatus('Saved'); setSync('In sync');
              } catch (e) {
                console.error('[wk3] image paste upload failed', e);
                setStatus('Image upload failed'); setSync('Error');
              }
            })();
            return true;
          },
          handleDrop: (view, event, _slice, _moved) => {
            if (!HAS_CONTEXT) return false;
            const files = event?.dataTransfer?.files;
            if (!files || !files.length) return false;
            const file = [...files].find(f => f.type.startsWith('image/'));
            if (!file) return false;
            event.preventDefault();
            (async () => {
              try {
                setStatus('Uploading image…'); setSync('Saving');
                const url = await uploadImageFile(file);
                const coords = view.posAtCoords({ left: event.clientX, top: event.clientY });
                if (coords) editor.chain().setTextSelection(coords.pos).setImage({ src: url, alt: '' }).run();
                else editor.chain().focus().setImage({ src: url, alt: '' }).run();
                scheduleAutosave();
                setStatus('Saved'); setSync('In sync');
              } catch (e) {
                console.error('[wk3] image drop upload failed', e);
                setStatus('Image upload failed'); setSync('Error');
              }
            })();
            return true;
          },
        },
      });

      const themeToggle = document.getElementById('wk3-theme-toggle');
      const editorWrap = document.querySelector('.editor-wrap');

      if (themeToggle && editorWrap) {
        themeToggle.addEventListener('click', () => {
          const isDark = editorWrap.getAttribute('data-theme') === 'dark';
          const newTheme = isDark ? 'light' : 'dark';
          editorWrap.setAttribute('data-theme', newTheme);
          localStorage.setItem('wk3-editor-theme', newTheme);
        });

        const savedTheme = localStorage.getItem('wk3-editor-theme');
        if (savedTheme) {
          editorWrap.setAttribute('data-theme', savedTheme);
        }
      }

      const mq = window.matchMedia('(prefers-color-scheme: dark)');
      if (mq && editorWrap) {
        if (!localStorage.getItem('wk3-editor-theme')) {
          editorWrap.setAttribute('data-theme', mq.matches ? 'dark' : 'light');
        }
        mq.addEventListener?.('change', (e) => {
          if (!localStorage.getItem('wk3-editor-theme')) {
            editorWrap.setAttribute('data-theme', e.matches ? 'dark' : 'light');
          }
        });
      }

      ensureTrailingParagraph(editor);
      editor.on('update', () => ensureTrailingParagraph(editor));
      function ensureTrailingParagraph(ed) {
        if (!HAS_CONTEXT) return;
        const last = ed?.state?.doc?.lastChild;
        if (!last) return;
        if (last.type.name === 'image') {
          ed.chain()
            .setTextSelection(ed.state.doc.content.size)
            .insertContent('<p></p>')
            .run();
        }
      }

      const toolbar = document.querySelector('.wk3-toolbar');
      const imgBtn = document.getElementById('wk3-insert-image');
      const imgInput = document.getElementById('wk3-image-input');

      if (imgInput && editor) {
        imgInput.addEventListener('change', async () => {
          const file = imgInput.files && imgInput.files[0];
          if (!file) return;
          if (!HAS_CONTEXT) { imgInput.value = ''; return; } 

          try {
            setStatus('Uploading image…'); 
            setSync('Saving');
            const url = await uploadImageFile(file);
            editor.chain().focus().setImage({ src: url, alt: '' }).run();
            scheduleAutosave();
            setStatus('Saved');
            setSync('In sync');
          } catch (e) {
            console.error('[wk3] toolbar image upload failed', e);
            setStatus('Image upload failed');
            setSync('Error');
          } finally {
            imgInput.value = ''; 
          }
        });
      }
      if (toolbar && editor) {
        toolbar.addEventListener('click', async e => {
          const btn = e.target.closest('.wk3-tool');
          if (!btn || btn.disabled) return;
          const cmd = btn.dataset.cmd;

          try {
            switch (cmd) {
              case 'undo': editor.chain().focus().undo().run(); break;
              case 'redo': editor.chain().focus().redo().run(); break;
              case 'bold': editor.chain().focus().toggleBold().run(); break;
              case 'italic': editor.chain().focus().toggleItalic().run(); break;
              case 'underline': editor.chain().focus().toggleUnderline().run(); break;
              case 'strike': editor.chain().focus().toggleStrike().run(); break;
              case 'heading1': editor.chain().focus().toggleHeading({ level: 1 }).run(); break;
              case 'heading2': editor.chain().focus().toggleHeading({ level: 2 }).run(); break;
              case 'paragraph': editor.chain().focus().setParagraph().run(); break;
              case 'bulletList': editor.chain().focus().toggleBulletList().run(); break;
              case 'orderedList': editor.chain().focus().toggleOrderedList().run(); break;
              case 'blockquote': editor.chain().focus().toggleBlockquote().run(); break;
              case 'codeBlock': editor.chain().focus().toggleCodeBlock().run(); break;
              case 'link': {
                const { empty } = editor.state.selection || {};
                if (empty) {
                  alert('Select some text to link first.');
                  break;
                }
                const current = editor.getAttributes('link')?.href || '';
                const input = prompt('Enter URL (leave empty to remove):', current);
                if (input === null) break;
                const href = input.trim();
                if (!href) {
                  editor.chain().focus().unsetLink().run();
                } else {
                  const formatted = /^[a-zA-Z][a-zA-Z0-9+.-]*:/.test(href) ? href : 'https://' + href;
                  editor.chain().focus().setLink({ href: formatted }).run();
                }
                scheduleAutosave();
                break;
              }
              case 'insertImage':
                imgInput.click();
                break;
            }
          } catch (err) {
            console.error('Toolbar command failed:', cmd, err);
          }
        });

        function qsCmd(cmd){ return toolbar?.querySelector(`.wk3-tool[data-cmd="${cmd}"]`) || null; }

        const btns = {
          undo:         qsCmd('undo'),
          redo:         qsCmd('redo'),
          bold:         qsCmd('bold'),
          italic:       qsCmd('italic'),
          underline:    qsCmd('underline'),
          strike:       qsCmd('strike'),
          heading1:     qsCmd('heading1'),
          heading2:     qsCmd('heading2'),
          paragraph:    qsCmd('paragraph'),
          bulletList:   qsCmd('bulletList'),
          orderedList:  qsCmd('orderedList'),
          blockquote:   qsCmd('blockquote'),
          codeBlock:    qsCmd('codeBlock'),
        };

        function setActive(el, active) {
          if (!el) return;
          el.classList.toggle('is-active', !!active);
          el.setAttribute('aria-pressed', active ? 'true' : 'false');
        }

        function setDisabled(el, disabled) {
          if (!el) return;
          el.disabled = !!disabled;
          el.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        }

        function syncToolbarState() {
          if (!editor) return;
          setActive(btns.bold,         editor.isActive('bold'));
          setActive(btns.italic,       editor.isActive('italic'));
          setActive(btns.underline,    editor.isActive('underline'));
          setActive(btns.strike,       editor.isActive('strike'));
          setActive(btns.blockquote,   editor.isActive('blockquote'));
          setActive(btns.codeBlock,    editor.isActive('codeBlock'));
          setActive(btns.bulletList,   editor.isActive('bulletList'));
          setActive(btns.orderedList,  editor.isActive('orderedList'));
          setActive(btns.paragraph,    editor.isActive('paragraph'));
          setActive(btns.heading1,     editor.isActive('heading', { level: 1 }));
          setActive(btns.heading2,     editor.isActive('heading', { level: 2 }));
          setDisabled(btns.undo, !editor.can().undo());
          setDisabled(btns.redo, !editor.can().redo());
        }

        if (editor) {
          syncToolbarState();
          editor.on('selectionUpdate', syncToolbarState);
          editor.on('transaction', syncToolbarState);
          editor.on('update', syncToolbarState);
        }

        bindImageReplace(editor);

        editor?.view?.dom.addEventListener('click', (e) => {
          const a = e.target.closest?.('a[href]');
          if (!a) return;
          const href = a.getAttribute('href');
          if (!href) return;
          const allowOpen = !HAS_CONTEXT || e.metaKey || e.ctrlKey;
          if (allowOpen) {
            e.preventDefault();
            window.open(href, '_blank', 'noopener,noreferrer');
          }
        });

        queueMicrotask(() => el.querySelector('.ProseMirror')?.focus());
        
        // @ts-ignore
        window.wk3 = { editor, get rev() { return rev; } };
      }
    }

    const AUTOSAVE_DELAY = 1200; 
    let autosaveTimer = null;
    let saving = false;
    let queued = false;

    const TYPING  = 'Typing...';
    const SAVING  = 'Saving...';
    const IDLE    = 'Idle';

    let teacherDirty = false;

    window.addEventListener('beforeunload', (e) => {
      const isStaff = (ROLE === 'teacher' || ROLE === 'admin');
      if (!isStaff) return;
      if (!teacherDirty) return;
      e.preventDefault();
      e.returnValue = ''; 
    });

    function scheduleAutosave() {
      if (!HAS_CONTEXT) return;
      if (IS_STAFF) return;               
      if (autosaveTimer) clearTimeout(autosaveTimer);
      autosaveTimer = setTimeout(() => {
        setStatus(SAVING);
        setSync('Saving');
        saveDraft(false);
      }, AUTOSAVE_DELAY);
    }

    function handleDocChangeUI() {
      if (!HAS_CONTEXT) return;

      if (IS_STAFF) {
        teacherDirty = true;
        markSnapshotGlow(true); 
        setStatus('Editing (no autosave)');
        setSync('Unsaved (manual snapshot)');
        return;
      }

      if ($status && $status.textContent !== SAVING) setStatus(TYPING);
      setSync('Unsaved changes');
      scheduleAutosave();
    }

    if (editor) {
      editor.on('transaction', ({ transaction }) => {
        if (!transaction.docChanged) return;
        handleDocChangeUI();
      });

      editor.on('blur', () => {
        if (HAS_CONTEXT && !IS_STAFF && !saving) saveDraft(false);
      });
    }

    document.addEventListener('keydown', (e) => {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's') {
        e.preventDefault();
        if (!HAS_CONTEXT) return;                
        if (autosaveTimer) clearTimeout(autosaveTimer);
        setStatus(SAVING);
        setSync('Saving');
        saveDraft(IS_STAFF);                     
      }
    });

    document.addEventListener('keydown', async (e) => {
      if (!editor || !HAS_CONTEXT) return;
      const isModK = (e.key.toLowerCase() === 'k') && (e.metaKey || e.ctrlKey);
      if (!isModK) return;
      e.preventDefault();
      const { empty } = editor.state.selection || {};
      if (empty) {
        alert('Select some text to add a link.');
        return;
      }
      const current = editor.getAttributes('link')?.href || '';
      const input = prompt('Enter URL (leave empty to remove link):', current);
      if (input === null) return;
      if (!input.trim()) {
        editor.chain().focus().unsetLink().run();
        scheduleAutosave();
        return;
      }
      let href = input.trim();
      if (!/^[a-zA-Z][a-zA-Z0-9+.-]*:/.test(href)) href = 'https://' + href;
      editor.chain().focus().setLink({ href }).run();
      scheduleAutosave();
    });

    async function saveDraft(isSnapshot = false) {
      clearTimeout(autosaveTimer); 
      if (!editor) return;
      if (!HAS_CONTEXT) return;  
      if (saving) { queued = true; return; }
      saving = true;
      setStatus('Saving...');

      const payload = isSnapshot ? {
        submission_id: SUBMISSION ? parseInt(SUBMISSION, 10) : undefined,
        body: editor.getText(),
        body_html: editor.getHTML(),
        rev,
        snapshot: true
      } : {
        autosave: true,
        submission_id: SUBMISSION ? parseInt(SUBMISSION, 10) : undefined,
        body: editor.getText(),
        body_html: editor.getHTML(),
        rev
      };

      try {
        const base = `/workspace/${TYPE}`;
        const url = SUBMISSION
          ? `${base}/save?submission_id=${encodeURIComponent(SUBMISSION)}`
          : `${base}/save`;

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
          try {
            const j = await res.json();
            expected = (typeof j?.expected === 'number') ? j.expected : null;
          } catch {}
          if (typeof expected === 'number') rev = expected;
          if (autosaveTimer) clearTimeout(autosaveTimer);
          try {
            editor?.setEditable(false);
          } catch (e) {
            console.warn('[wk3] failed to lock editor after 409', e);
          }
          console.warn('[wk3] 409 conflict detected, rev now:', rev);
          setStatus('Conflict – newer version exists');
          setSync('Out of date');
          showToast('Your teacher or another session has a newer version. Reload to sync.');
          alert('This page is out of date.\n\nAnother session has saved a newer version.\nPlease click "Reload" in the yellow box to sync before editing.');
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
          if (typeof j?.rev === 'number') rev = j.rev;
          else rev += 1;
        } catch {
          rev += 1;
        }

        setStatus(isSnapshot ? 'Snapshot saved' : 'Saved');
        setSavedAt(new Date().toLocaleTimeString());
        setSync('In sync');
        hideToast();

        if (isSnapshot) {
          teacherDirty = false;
          markSnapshotGlow(false);  
        }

      } catch (e) {
        console.error('[wk3] save error', e);
        setStatus('Error (network)');
        setSync('Error');
      } finally {
        saving = false;
        if (queued) { queued = false; saveDraft(false); }
      }
    } 

    let pendingAfterOnline = false;

    window.addEventListener('offline', () => {
      setStatus('Offline');
      setSync('Offline — changes queued');
      pendingAfterOnline = true;
    });

    window.addEventListener('online', () => {
      setStatus('Reconnected');
      setSync('Reconnected — saving…');
      if (HAS_CONTEXT && editor && typeof saveDraft === 'function') {
        if (!saving) {
          saveDraft(false);         
        } else {
          queued = true;             
        }
      }
    });

    window.saveDraft = saveDraft;

    function renderDetailLoading(id) {
      $detail.innerHTML = `<div class="td-head"><strong>Thread #${id}</strong><span class="td-meta">Loading…</span></div>`;
    }

    function renderDetailEmpty() {
      $detail.innerHTML = `<div class="td-empty">Select a thread to view messages here.</div>`;
      if ($replyBox) $replyBox.style.display = 'none';
      selectedThreadId = null;
      if ($list) {
        $list.querySelectorAll('.thread-card').forEach(el => {
          el.classList.remove('active');
          el.style.display = '';
        });
      }
    }

    function escapeHtml(s){
      return (s ?? '').replace(/[&<>"']/g, c => ({
        '&':'&amp;',
        '<':'&lt;',
        '>':'&gt;',
        '"':'&quot;',
        "'":'&#39;'
      }[c]));
    }

    function renderDetail(thread) {
      const isResolved = !!thread.is_resolved;
      const label      = thread.label || (isResolved ? 'Resolved' : '');

      let resolveBtn = '';
      if (IS_STAFF && !isResolved) {
        resolveBtn = `
          <button type="button"
                  class="btn td-resolve-btn"
                  data-thread-id="${thread.id}">
            Resolve
          </button>
        `;
      }

      const head = `
        <div class="td-head">
          <div>
            <strong>Thread #${thread.id}</strong>
            ${label ? `<span class="pill" style="margin-left:8px;">${label}</span>` : ``}
          </div>
          <div class="td-head-right">
            <span class="td-meta">${thread.created_at || ''}</span>
            ${resolveBtn}
          </div>
        </div>
      `;

      const sel = thread.selection_text
        ? `<div class="sel"><em>&ldquo;${escapeHtml(thread.selection_text)}&rdquo;</em></div>`
        : ``;

      const meName = (window.AUTH_NAME || '').trim().toLowerCase();

      const msgs = (thread.messages || []).map(m => {
        const rawName  = m.author?.name || m.from || 'User';
        const normName = rawName.trim().toLowerCase();

        const isMe  = meName && normName === meName;
        const rowCl = isMe ? 'td-msg-row from-me' : 'td-msg-row from-them';
        const msgCl = isMe ? 'td-msg from-me'      : 'td-msg from-them';

        const who  = escapeHtml(rawName);
        const when = escapeHtml(m.created_at || '');
        const body = escapeHtml(m.body || '');

        return `
          <div class="${rowCl}">
            <div class="${msgCl}">
              <div class="who">${who}</div>
              <div class="td-meta">${when}</div>
              <div style="white-space:pre-wrap;">${body}</div>
            </div>
          </div>
        `;
      }).join('');

      $detail.innerHTML =
        head + sel + (msgs || `<div class="td-empty" style="margin-top:6px;">No messages.</div>`);

      if ($replyBox) $replyBox.style.display = 'block';
      if ($replyInput) $replyInput.focus();
    }
    if ($detail) {
      $detail.addEventListener('click', async (ev) => {
        const btn = ev.target.closest('.td-resolve-btn');
        if (!btn) return;

        const id = parseInt(btn.dataset.threadId || '0', 10);
        if (!id) return;

        btn.disabled = true;
        const originalText = btn.textContent;
        btn.textContent = 'Resolving…';

        try {
          const res = await fetch(`/workspace/${TYPE}/thread/${id}/resolve`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': CSRF,
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({}),
          });

          if (!res.ok) {
            console.error('[wk3] resolve failed', res.status, await res.text().catch(() => ''));
            alert('Could not mark this thread as resolved (server error).');
            btn.disabled = false;
            btn.textContent = originalText;
            return;
          }

          let data = null;
          try { data = await res.json(); } catch {}

          const thread = data && data.thread
            ? data.thread
            : { id, is_resolved: true, label: 'Resolved' };

          renderDetail(thread);

          const card = $list?.querySelector(`.thread-card[data-thread-id="${id}"]`);
          if (card) {
            card.classList.remove('thread-overdue', 'thread-student-request');
            card.classList.add('thread-resolved');
            const statusPill = card.querySelector('.pill:not(.pill-student-request)');
            if (statusPill) {
              statusPill.textContent = 'Resolved';
              statusPill.classList.add('pill-resolved');
              statusPill.classList.remove('pill-overdue');
            }
          }
        } catch (e) {
          console.error('[wk3] resolve error', e);
          alert('Network error while resolving this thread.');
          btn.disabled = false;
          btn.textContent = originalText;
        }
      });
    }
    if ($list) {
      $list.addEventListener('click', async (ev) => {
        const card = ev.target.closest('.thread-card');
        if (!card) return;

        $list.querySelectorAll('.thread-card').forEach(el => {
          el.classList.remove('active');
          el.style.display = '';
        });

        card.classList.add('active');
        card.style.display = 'none';

        const id = parseInt(card.dataset.threadId || '0', 10);
        if (!id) return;
        selectedThreadId = id;

        const from = parseInt(card.dataset.from || '', 10);
        const to   = parseInt(card.dataset.to   || '', 10);
        if (editor && Number.isFinite(from) && Number.isFinite(to) && to > from) {
          try {
            editor.chain().setTextSelection({ from, to }).run();
            editor.view.scrollIntoView();
          } catch (e) {
            console.warn('[wk3] setTextSelection failed', e);
          }
        }

        try {
          renderDetailLoading(id);
          const res = await fetch(`/workspace/${TYPE}/thread/${id}`, {
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
          });

          if (!res.ok) {
            $detail.innerHTML = `<div class="td-empty">Couldn't load thread (#${id}).</div>`;
            if ($replyBox) $replyBox.style.display = 'none';
            console.error('[wk3] thread load failed', res.status, await res.text().catch(() => ''));
            return;
          }

          const data = await res.json().catch(() => null);
          renderDetail(data || { id });
        } catch (e) {
          $detail.innerHTML = `<div class="td-empty">Network error loading thread (#${id}).</div>`;
          if ($replyBox) $replyBox.style.display = 'none';
          console.error('[wk3] thread load error', e);
        }
      });
    }

    async function openThreadFromSelection(options = {}) {
      if (!editor) return;
      if (!SUBMISSION) { alert('Missing submission id. Reload this page.'); return; }

      const { presetBody = null } = options;

      const sel  = editor.state.selection;
      const from = sel?.from ?? 0;
      const to   = sel?.to ?? 0;
      if (!Number.isFinite(from) || !Number.isFinite(to) || to <= from) { alert('Select some text first.'); return; }

      let selectionText = '';
      try { selectionText = editor.state.doc.textBetween(from, to, ' '); } catch {}

      let body = presetBody;
      if (!body) {
        body = prompt('First message for this thread:', '');
        if (body === null) return;
      }

      const trimmed = (body || '').trim();
      if (!trimmed) { alert('Message can’t be empty.'); return; }

      try {
        const url = `/workspace/${TYPE}/thread?submission_id=${encodeURIComponent(SUBMISSION)}`;
        const payload = {
          submission_id: parseInt(SUBMISSION, 10),
          selection_text: selectionText.slice(0, 255),
          body: trimmed,
          start_offset: from,
          end_offset: to,
        };
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

        if (!res.ok) {
          let msg = '';
          try { msg = (await res.json()).message || '' } catch { msg = await res.text().catch(()=> '') }
          console.error('[wk3] open thread failed', res.status, msg);
          alert(`Could not open the thread (HTTP ${res.status})${msg ? `\n${msg}` : ''}.`);
          return;
        }

        location.reload();
      } catch (e) {
        console.error('[wk3] open thread error', e);
        alert('Network error while opening the thread.');
      }
    }

    async function sendReply() {
      if (!selectedThreadId) return;
      const body = ($replyInput?.value || '').trim();
      if (!body) {
        setReplyStat('Type a message first.');
        return;
      }

      setReplyStat('Sending…');

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
        });

        const contentType = res.headers.get('Content-Type') || '';
        if (res.ok && contentType.includes('application/json')) {
          const data = await res.json().catch(() => ({}));
          if (data && data.thread) {
            renderDetail(data.thread);
          } else {
            const again = await fetch(`/workspace/${TYPE}/thread/${selectedThreadId}`, {
              headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              },
              credentials: 'same-origin',
            });
            const j = await again.json().catch(() => null);
            if (j) renderDetail(j);
          }
        } else if (res.ok) {
          const again = await fetch(`/workspace/${TYPE}/thread/${selectedThreadId}`, {
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
          });
          const j = await again.json().catch(() => null);
          if (j) renderDetail(j);
        } else {
          console.error('[wk3] reply failed', res.status, await res.text().catch(() => ''));
          setReplyStat(`Error (${res.status})`);
          return;
        }

        if ($replyInput) $replyInput.value = '';
        setReplyStat('Sent');
        setTimeout(() => setReplyStat('Ready'), 1200);
      } catch (e) {
        console.error('[wk3] reply error', e);
        setReplyStat('Network error');
      }
    }

    if ($replyBtn) {
      $replyBtn.addEventListener('click', (e) => {
        e.preventDefault();
        sendReply();
      });
    }

    if ($replyInput) {
      $replyInput.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
          e.preventDefault();
          sendReply();
        }
      });
    }

    if ($replyClose) {
      $replyClose.addEventListener('click', () => {
        renderDetailEmpty();
      });
    }

    const snapshotBtn = document.getElementById('wk3-btn-save');
    if (snapshotBtn && (ROLE === 'teacher' || ROLE === 'admin')) {
      snapshotBtn.addEventListener('click', () => {
        saveDraft(true);
      });
    }

    function markSnapshotGlow(on = true) {
      const btn = document.getElementById('wk3-btn-save');
      if (!btn) return;
      btn.classList.toggle('glow', !!on);
    }

    const requestBtn    = document.getElementById('wk3-btn-request');
    const openThreadBtn = document.getElementById('wk3-open-thread');

    if (requestBtn) {
      requestBtn.addEventListener('click', () => {
        if (!editor) return;
        if (!SUBMISSION) {
          alert('Cannot send feedback request: missing submission id. Please reload this page.');
          return;
        }
        openThreadFromSelection({ isStudentRequest: true });
      });
    }

    if (openThreadBtn) {
      openThreadBtn.addEventListener('click', () => {
        if (!editor) return;
        if (!SUBMISSION) {
          alert('Cannot open thread: missing submission id. Please reload this page.');
          return;
        }
        openThreadFromSelection({});
      });
    }

    document.getElementById('wk3-btn-toggle-threads')?.addEventListener('click', () => {
      const rightPane = document.querySelector('.right');
      const leftPane  = document.querySelector('.left');
      const btn       = document.getElementById('wk3-btn-toggle-threads');
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

    const $history      = document.getElementById('wk3-history');
    const $historyBody  = document.getElementById('wk3-history-body');
    const $historyClose = document.getElementById('wk3-history-close');

    document.getElementById('wk3-btn-history')?.addEventListener('click', async () => {
      if (!$history) return;
      $history.style.display = 'flex';
      $historyBody.innerHTML = '<div style="opacity:.7; font-style:italic;">Loading…</div>';

      try {
        const url = (IS_STAFF && STUDENT_ID)
          ? `/workspace/${TYPE}/history?student=${STUDENT_ID}`
          : `/workspace/${TYPE}/history`;

        const res = await fetch(url, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
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
                <strong>#${v.id}</strong> — ${v.created_at_human || v.created_at || ''}
                <div style="font-size:12px; color:#888;">
                  ${v.created_at_full || ''}
                </div>
                <div style="font-size:12px; color:#666; margin-top:2px;">
                Created by: ${v.by_name}
                </div>
                <div style="font-size:13px; color:#555; margin-top:4px;">
                  ${v.summary || '(no summary)'}
                </div>
              </div>
              ${IS_STAFF
                ? `<button class="btn wk3-restore" data-version="${v.id}" type="button">Restore</button>`
                : `<button class="btn wk3-download" data-version="${v.id}" type="button">Download</button>`
              }
            </div>
          </div>
        `).join('');

      } catch (err) {
        console.error('[wk3] history load failed', err);
        $historyBody.innerHTML = '<div style="color:#b00;">Error loading history.</div>';
      }
    });

    $historyBody?.addEventListener('click', async (e) => {
      const btn = e.target.closest('.wk3-restore, .wk3-download');
      if (!btn) return;
      const ver = parseInt(btn.dataset.version || '0', 10);
      if (!ver) return;
      const isRestore = btn.classList.contains('wk3-restore');
      const originalLabel = btn.textContent;
      btn.disabled = true;
      btn.textContent = isRestore ? 'Preparing download…' : 'Downloading…';
      try {
        window.location.href = `/workspace/${TYPE}/export?student=${STUDENT_ID}&version=${ver}`;
      } catch (err) {
        console.error('[wk3] export-version error', err);
        btn.disabled = false;
        btn.textContent = originalLabel;
      }
    });

    $historyClose?.addEventListener('click', () => {
      if ($history) $history.style.display = 'none';
    });

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

    const $successToast = document.getElementById('wk3-toast-success');
    function showSuccessToast() {
      if ($successToast) {
        $successToast.style.display = 'inline-flex';
        setTimeout(() => { $successToast.style.display = 'none'; }, 3000);
      }
    }
    (function checkRestoredParam() {
      const params = new URLSearchParams(window.location.search);
      if (params.get('restored') === '1') {
        showSuccessToast();
        params.delete('restored');
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({}, '', newUrl);
      }
    })();

    setSync('In sync');
      
    const msgBtn   = document.getElementById('wk3-btn-messages');
    const msgPanel = document.getElementById('msg-panel');
    const msgClose = document.getElementById('msg-close');
    const msgList  = document.getElementById('msg-list');
    const msgForm  = document.getElementById('msg-form');
    const msgBadge = document.getElementById('wk3-msg-badge');

    function fmtDubai(iso) {
      try {
        if (!iso) return '';
        return new Date(iso).toLocaleString('en-GB', {
          timeZone: 'Asia/Dubai',
          hour12: false,
        });
      } catch {
        return iso || '';
      }
    }

    function getMsgStorageKey() {
      const subId = el?.dataset.submissionId || '';
      if (!subId) return null;
      return `wk3-msg-seen-${subId}`;
    }

    function getSeenCount() {
      const key = getMsgStorageKey();
      if (!key) return 0;
      try {
        const raw = window.localStorage?.getItem(key);
        const n   = parseInt(raw, 10);
        return Number.isFinite(n) && n >= 0 ? n : 0;
      } catch {
        return 0;
      }
    }

    function setSeenCount(n) {
      const key = getMsgStorageKey();
      if (!key) return;
      try {
        window.localStorage?.setItem(key, String(Math.max(0, n | 0)));
      } catch {
      }
    }

    function updateMsgBadge(unread) {
      if (!msgBadge) return;
      if (IS_STAFF) {
        msgBadge.textContent = '';
        msgBadge.style.display = 'none';
        return;
      }
      const n = Number.isFinite(unread) ? unread : 0;
      if (n > 0) {
        msgBadge.textContent = String(n);
        msgBadge.style.display = 'inline-flex';
      } else {
        msgBadge.textContent = '';
        msgBadge.style.display = 'none';
      }
    }

    function renderMessages(messages) {
      if (!Array.isArray(messages) || messages.length === 0) {
        msgList.innerHTML = '<p class="td-empty" style="margin:6px 0;">No messages yet.</p>';
        return;
      }

      const meId   = String(window.AUTH_ID ?? '');
      const meName = window.AUTH_NAME || 'You';
      const other  = window.COUNTERPART_NAME || 'User';

      msgList.innerHTML = messages.map(m => {
        const senderId = String(m.sender_id ?? m.author_id ?? '');
        const who = (senderId && senderId === meId)
          ? meName
          : (m.from || m.author?.name || other);

        const when = fmtDubai(m.created_at);
        const body = String(m.body || '')
          .replace(/&/g,'&amp;')
          .replace(/</g,'&lt;')
          .replace(/>/g,'&gt;')
          .replace(/\n/g,'<br>');

        return `
          <div class="td-msg" style="border-top:1px solid #eee; padding-top:6px; margin-top:6px;">
            <div class="who">${who}</div>
            <div class="td-meta">${when}</div>
            <div style="margin-top:6px;">${body}</div>
          </div>`;
      }).join('');

      msgList.scrollTop = msgList.scrollHeight;
    }

    function toggleMessages(show = null) {
      if (!msgPanel) return;
      const visible  = msgPanel.style.display === 'block';
      const willShow = (show === null ? !visible : !!show);
      msgPanel.style.display = willShow ? 'block' : 'none';
      if (willShow) {
        loadMessages({ markSeen: true });
      }
    }

    async function loadMessages(opts = {}) {
      const { markSeen = false } = opts;
      msgList.innerHTML = '<p class="td-empty" style="margin:6px 0;">Loading…</p>';
      const type = TYPE || 'essay';
      const id   = el?.dataset.submissionId;
      if (!id) {
        msgList.innerHTML = '<p class="td-empty">Open a student workspace to view messages.</p>';
        if (msgForm) msgForm.style.display = 'none';
        updateMsgBadge(0);
        return;
      }

      try {
        const res = await fetch(`/workspace/${type}/general/${id}`, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data      = await res.json().catch(() => ({}));
        const messages = Array.isArray(data.messages) ? data.messages : [];
        const total    = messages.length;

        if (IS_STAFF) {
          let seen = getSeenCount();
          if (seen > total) seen = total;
          if (seen < 0)      seen = 0;
          if (markSeen) {
            seen = total;
          }
          setSeenCount(seen);
          const unread = Math.max(total - seen, 0);
          updateMsgBadge(unread);
        } else {
          setSeenCount(total);
          updateMsgBadge(0);
        }

        renderMessages(messages);
        if (msgForm) msgForm.style.display = '';
      } catch (e) {
        console.error('[wk3] load messages failed', e);
        msgList.innerHTML = '<p class="td-empty">Error loading messages.</p>';
        if (msgForm) msgForm.style.display = 'none';
        updateMsgBadge(0);
      }
    }

    msgForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const type = TYPE || 'essay';
      const id   = el?.dataset.submissionId;
      if (!id) return;
      const ta   = msgForm.querySelector('textarea');
      const body = (ta?.value || '').trim();
      if (!body) return;
      const optimistic = [{
        sender_id: window.AUTH_ID,
        body,
        created_at: new Date().toISOString(),
        from: window.AUTH_NAME,
      }];
      const prev = msgList.innerHTML;
      renderMessages(optimistic);
      msgList.innerHTML += prev;

      try {
        const res = await fetch(`/workspace/${type}/general/${id}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
          body: JSON.stringify({ body }),
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        if (ta) ta.value = '';
        await loadMessages({ markSeen: true });
      } catch (e) {
        console.error('[wk3] send message failed', e);
        alert('Failed to send message.');
      }
    });

    msgBtn?.addEventListener('click', () => toggleMessages());
    msgClose?.addEventListener('click', () => toggleMessages(false));
    document.addEventListener('click', (e) => {
      if (msgPanel?.style.display === 'block' &&
          !msgPanel.contains(e.target) &&
          e.target !== msgBtn) {
        toggleMessages(false);
      }
    });

    if (msgList && el?.dataset.submissionId) {
      loadMessages({ markSeen: false });
    }
  </script>
</body>
</html>