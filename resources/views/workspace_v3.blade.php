<!-- V3-SENTINEL: workspace_v3.blade.php -->
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Workspace V3 — {{ isset($type) ? ucfirst($type) : 'Workspace' }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    /* removed the short stub :root to avoid conflicts */

    html,body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background:var(--bg); }

    /* keep layout only; drop background/border to avoid token conflicts */
    .topbar,.bottombar {
      display:flex; justify-content:space-between; align-items:center;
      padding:12px 16px;
    }

    /* === THEME TOKENS === */
:root {
  /* light (defaults you already have) */
  --bg: #ffffff;
  --fg: #0f172a;          /* text */
  --muted: #64748b;       /* secondary text */
  --pane: #fafafa;        /* right column */
  --b: #e5e7eb;           /* borders */
  --btn-bg: #ffffff;
  --btn-fg: #0f172a;
  --btn-b: #e5e7eb;
  --btn-hover: #f4f4f5;
  --code-bg: #f6f8fa;
}

/* Dark theme tokens (activated via [data-theme="dark"] on .editor-wrap) */
.editor-wrap[data-theme="dark"] {
  --bg: #0b0f17;
  --fg: #e5e7eb;
  --muted: #9aa4b2;
  --pane: #111827;
  --b: #1f2937;
  --btn-bg: #0f1621;
  --btn-fg: #e5e7eb;
  --btn-b: #2b3645;
  --btn-hover: #152131;
  --code-bg: #0f1b2a;
}

/* Apply theme tokens */
html, body { background: var(--bg); color: var(--fg); }
.left { background: var(--bg); }
.right { background: var(--pane); border-left: 1px solid var(--b); }
.left { border-right: 1px solid var(--b); }

/* Editor surface */
.editor { background: var(--bg); }
.editor .ProseMirror {
  color: var(--fg);
}
/* Editor scroll + breathing room so caret isn’t trapped */
/* (single source of truth lives in the larger .editor block below) */

.editor .ProseMirror {
  padding-bottom: 200px;     /* ← space after the last node (text or image) */
}
.editor .ProseMirror p,
.editor .ProseMirror li { color: var(--fg); }
.editor .ProseMirror blockquote {
  border-left: 3px solid var(--b);
  color: var(--muted);
}
.editor .ProseMirror hr { border: 0; border-top: 1px solid var(--b); }
.editor .ProseMirror code { background: var(--code-bg); padding: 0.15em 0.35em; border-radius: 6px; }
.editor .ProseMirror pre { background: var(--code-bg); padding: 12px; border-radius: 10px; }

/* Toolbar + buttons */
.topbar, .bottombar { background: var(--bg); border-color: var(--b); }

/* Universal button style — works for both <button> and <a> */
button.btn,
a.btn {
  appearance: none;
  border: 1px solid var(--b);
  background: var(--btn-bg);
  color: var(--btn-fg);
  border-color: var(--btn-b);
  padding: 8px 12px;
  border-radius: 10px;
  cursor: pointer;
  line-height: 1;
  white-space: nowrap;
  text-decoration: none;       /* remove link underline */
  display: inline-flex;        /* normalize layout */
  align-items: center;
  justify-content: center;
  font-family: inherit;
  font-size: 14px;
  transition: background 0.15s ease, box-shadow 0.15s ease;
}

/* Hover / active states */
button.btn:hover,
a.btn:hover {
  background: var(--btn-hover, #f5f5f5);
  text-decoration: none;
}

button.btn:active,
a.btn:active {
  transform: translateY(1px);
}

/* Disabled state */
button.btn[disabled],
a.btn[aria-disabled="true"] {
  opacity: 0.6;
  pointer-events: none;
  cursor: not-allowed;
}

/* Secondary variant (if used in your theme) */
button.btn.secondary,
a.btn.secondary {
  background: #f5f5f5;
  color: #333;
  border-color: #ddd;
}

/* Optional: focus ring for accessibility */
button.btn:focus-visible,
a.btn:focus-visible {
  outline: 2px solid #0b6bd6;
  outline-offset: 2px;
}

/* Enhanced red glow for Snapshot button — noticeable but not harsh */
@keyframes pulseGlow {
  0% {
    box-shadow:
      0 0 0 rgba(239,68,68,0.0),
      0 0 0 rgba(239,68,68,0.0);
  }
  50% {
    box-shadow:
      0 0 6px rgba(239,68,68,0.55),
      0 0 12px rgba(239,68,68,0.35);
  }
  100% {
    box-shadow:
      0 0 0 rgba(239,68,68,0.0),
      0 0 0 rgba(239,68,68,0.0);
  }
}

.glow {
  animation: pulseGlow 1.8s ease-in-out infinite;
}

/* Respect reduced-motion preferences */
@media (prefers-reduced-motion: reduce) {
  .glow { animation: none; }
}
/* Thread list cards */
.thread-card { background: var(--bg); border-color: var(--b); }

/* retain layout/shape without overriding theme colors */
.thread-card {
  border:1px solid var(--b);
  border-radius:10px;
  padding:10px;
  margin-bottom:10px;
  cursor:pointer;
}
.thread-card:hover { box-shadow:0 0 0 2px #eef; }
.thread-card.active { outline:2px solid #a7c4ff; }

.td-empty { background: var(--bg); border-color: var(--b); color: var(--muted); }
.td-empty {
  opacity:.7; font-style:italic; padding:8px 12px;
  border:1px dashed var(--b); border-radius:8px;
}

/* Pills + subtle elements */
.pill { background: rgba(148,163,184,.12); border-color: var(--b); color: var(--fg); }
.pill-bad { color: #f87171; }
.pill-student-request { background: rgba(34,197,94,.12); border-color: rgba(34,197,94,.35); color:#166534; }
.thread-student-request { border-left:3px solid rgba(34,197,94,.7); }

/* Toasts */
#wk3-toast { background: rgba(250, 204, 21, .1); border:1px solid #facc15; }
#wk3-toast-success { background: rgba(34,197,94,.12); border:1px solid #22c55e; color: #0f5132; }

    .topbar { gap:12px; }
    .topbar > .utils { display:flex; align-items:center; gap:8px; }
    .bottombar { border-top:1px solid var(--b); border-bottom:none; }

    .main { display:grid; grid-template-columns: 1fr 360px; min-height: calc(100dvh - 96px); }
    .left { border-right:1px solid var(--b); background:var(--bg); min-width:0; }
    .right { background:var(--pane); overflow:auto; }

    /* single authoritative editor block (removed earlier duplicate overflow rule) */
    .editor {
      height: calc(100dvh - 120px);
      overflow: auto;              /* ← editor pane scrolls */
      padding: 16px;
      position: relative;
    }
    .editor .ProseMirror { min-height: 60vh; outline: none; cursor: text; user-select: text; -webkit-user-select: text; }
    .editor .ProseMirror:focus { outline: none; }

/* === Image display control (Phase 3 Step 1.1 refined) === */
.editor .ProseMirror img {
  max-width: 720px;        /* limit visual width */
  width: 100%;             /* responsive inside smaller screens */
  height: auto;
  display: block;
  margin: 16px auto;
  border-radius: 6px;
  object-fit: contain;
  box-shadow: 0 0 6px rgba(0, 0, 0, 0.1);
}

/* --- WK3 Toolbar --- */
.wk3-toolbar {
  position: sticky; top: 0; z-index: 50;
  display: flex; gap: 6px; align-items: center;
  padding: 8px; border-bottom: 1px solid var(--b); background: var(--bg);
}
.wk3-tool {
  appearance: none; border:1px solid var(--b); background: var(--btn-bg);
  color: var(--btn-fg);
  padding:6px 8px; border-radius:8px; cursor:pointer; line-height:1;
  font-size:13px;
}
.wk3-tool:hover { background: var(--btn-hover); }
.wk3-tool[disabled] { opacity:.5; cursor:not-allowed; }
.wk3-tool.is-active { outline:2px solid #a7c4ff; }
.wk3-sep { width:1px; height:22px; background:#eee; margin:0 2px; }

/* --- Theme toggle (🌞 | 🌜) --- */
.wk3-theme-toggle{
  display:inline-flex; align-items:center; gap:8px;
  padding:4px 10px; border:1px solid var(--b); border-radius:999px;
  background: var(--btn-bg); color: var(--btn-fg);
  cursor:pointer; user-select:none;
}
.wk3-theme-toggle .sun,
.wk3-theme-toggle .moon{
  font-size:14px; line-height:1;
  opacity:.6; transition:opacity .15s ease, transform .15s ease;
}
.wk3-theme-toggle .sun, .wk3-theme-toggle .moon { opacity:.55; transition:opacity .15s ease; }
.editor-wrap[data-theme="light"] .wk3-theme-toggle .sun { opacity:1; }
.editor-wrap[data-theme="dark"]  .wk3-theme-toggle .moon { opacity:1; }
/* Active icon emphasis based on editor theme */
.editor-wrap[data-theme="light"] .wk3-theme-toggle .sun { opacity:1; transform:translateY(-1px); }
.editor-wrap[data-theme="light"] .wk3-theme-toggle .moon{ opacity:.45; }

.editor-wrap[data-theme="dark"]  .wk3-theme-toggle .moon{ opacity:1; transform:translateY(-1px); }
.editor-wrap[data-theme="dark"]  .wk3-theme-toggle .sun { opacity:.45; }

/* Focus ring for keyboard users */
.wk3-theme-toggle:focus-visible{
  outline:2px solid #a7c4ff; outline-offset:2px;
}

    .brand { font-weight:700; }
    .bottombar{
      position: fixed; left: 0; right: 0; bottom: 0;
      display: flex; justify-content: space-between; align-items: center;
      background: var(--bg); border-top: 1px solid var(--b);
      padding: 10px 16px; z-index: 200;
    }
    .td-head { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; }
    
    /* Clean thread header typography */
.td-head strong {
  font-size: 15px;
  font-weight: 600;
}

.td-head .pill {
  font-size: 13px;
  font-weight: 500;
}

.td-head .td-meta {
  font-size: 13px;       /* match label size */
  color: var(--muted);
}

/* Thread message rows + bubbles */
.td-msg-row {
  display: flex;
  margin: 6px 8px 10px;   /* ← add 8px left and right spacing */
  padding: 0 4px;         /* optional: tiny internal breathing room */
}

/* Base bubble */
.td-msg {
  max-width: 85%;              /* comfortable width */
  padding: 8px 10px 10px;
  border-radius: 14px;
  border: 1px solid var(--b);
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
  background: var(--bg);
}

/* “Me” – right side */
.td-msg-row.from-me {
  justify-content: flex-end;
}
.td-msg-row.from-me .td-msg {
  margin-left: auto;           /* ensures right alignment */
}

/* “Them” – left side */
.td-msg-row.from-them {
  justify-content: flex-start;
}
.td-msg-row.from-them .td-msg {
  margin-right: auto;          /* ensures alignment */
}

/* Me bubble colours */
.td-msg.from-me {
  background: #0b6bd6;
  color: #ffffff;
  border-color: #0b6bd6;
}

/* Them bubble colours */
.td-msg.from-them {
  background: rgba(148, 163, 184, 0.12);
  border-color: rgba(148, 163, 184, 0.35);
}

/* Inner text styles */
.td-msg .who {
  font-size: 11px;
  font-weight: 300;
  opacity: 0.65;
  margin-bottom: 2px;
}

.td-msg .td-meta {
  font-size: 11px;
  color: var(--muted);
  margin-bottom: 6px;
}

/* Me message inner text tint */
.td-msg.from-me .who {
  color: #e0f2fe;
}
.td-msg.from-me .td-meta {
  color: rgba(226, 232, 240, 0.85);
}

/* Message body */
.td-msg > div:last-child {
  font-size: 15px;
  line-height: 1.5;
}

.sel { margin:8px 0; padding:6px 8px; background:#fffbea; border:1px solid #f6e5a8; border-radius:8px; }

#wk3-reply { display:none; padding:0 12px 12px; border-top:1px solid var(--b); background:#fafafa; position:sticky; bottom:0; }
#wk3-reply textarea {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;   /* ← stops overflow on the right */
  min-height: 70px;
  resize: vertical;
  padding: 10px;
  border: 1px solid var(--b);
  border-radius: 10px;
  font: inherit;
  background: #fff;
}
#wk3-reply .row { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:8px; }
#wk3-reply small { color:#777; }

.msg-badge {
  margin-left: 6px;
  padding: 0 6px;
  min-width: 18px;

  border-radius: 999px;         /* pill */
  background: #ef4444;          /* red (WhatsApp-ish, but on-brand enough) */
  color: #ffffff;
  font-size: 11px;
  line-height: 1.4;
  font-weight: 600;

  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* --- Unify status bar font size --- */
.wk3-status span,
.wk3-status small {
  font-size: 11px;
}
  </style>
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
  <!-- rest of your document (unchanged)… -->
  <header class="topbar" role="banner">
<div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">

  <strong class="brand">ToK V2</strong>
  <span style="margin:0 8px; color:#bbb;">&bull;</span>
  <span>{{ isset($type) ? ucfirst($type) : 'Submission' }}</span>

  @php
    $isStudent = optional(Auth::user())->role === 'student';
  @endphp

  @if ($isStudent && !empty($supervisorLabel))
    <span
      class="pill"
      style="display:inline-flex; align-items:center; gap:4px; font-size:13px;"
    >
      <span style="opacity:.75;">ToK Supervisor</span>
      <span style="font-weight:600;">{{ $supervisorLabel }}</span>
    </span>
  @endif

</div>
    <!-- Utilities only (no primary actions live in header) -->
    <div class="utils">
      <button class="btn" id="wk3-btn-messages" type="button" aria-expanded="false">
    Messages
    <span id="wk3-msg-badge" class="msg-badge"></span>
  </button>
  
  <!-- Messages panel (legacy) -->
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
      <button class="btn" id="wk3-btn-resources" type="button">ToK Resources</button>
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
  <!-- WK3 Toolbar (Phase 3) -->
  <div class="wk3-toolbar" aria-label="Editor toolbar">
    <button type="button" class="wk3-tool" data-cmd="undo" title="Undo">↶</button>
    <button type="button" class="wk3-tool" data-cmd="redo" title="Redo">↷</button>

    <span class="wk3-sep" aria-hidden="true"></span>

    <button type="button" class="wk3-tool" data-cmd="bold" title="Bold"><strong>B</strong></button>
    <button type="button" class="wk3-tool" data-cmd="italic" title="Italic"><em>I</em></button>
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
  </div> <!-- /.wk3-toolbar -->

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
              $ownerId      = optional(optional($t->version)->submission)->student_id;
              $lastAuthorId = optional($t->latestMessage)->author_id;
              $label = $t->is_resolved
                ? 'Resolved'
                : (($lastAuthorId && $ownerId && (int)$lastAuthorId === (int)$ownerId) ? 'Awaiting Teacher' : 'Awaiting Student');

              $latestBody = trim((string) optional($t->latestMessage)->body);
              $latestBody = $latestBody === '' ? '—' : mb_strimwidth($latestBody, 0, 140, '…');

              $pmFrom = is_numeric($t->pm_from ?? null) ? (int)$t->pm_from : null;
              $pmTo   = is_numeric($t->pm_to   ?? null) ? (int)$t->pm_to   : null;

              $firstMessage   = $t->messages->first();
              $firstAuthorId  = optional($firstMessage)->author_id;
              $isStudentRequest = $ownerId && $firstAuthorId && (int)$firstAuthorId === (int)$ownerId;
            @endphp

            <div class="thread-card {{ $isStudentRequest ? 'thread-student-request' : '' }}"
                 data-thread-id="{{ $t->id }}"
                 @if($pmFrom !== null && $pmTo !== null)
                   data-from="{{ $pmFrom }}" data-to="{{ $pmTo }}"
                 @endif>
              <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                <div>
                  <strong>#{{ $t->id }}</strong>
                  <span style="color:#777;"> - {{ optional($t->created_at)->format('Y-m-d H:i') }}</span>
                </div>
                <div style="display:flex; gap:6px; align-items:center;">
                  @if($isStudentRequest)
                    <span class="pill pill-student-request">Student request</span>
                  @endif
                  <span class="pill">{{ $label }}</span>
                </div>
              </div>

              @if (!empty($t->selection_text))
                <div class="sel"><em>&ldquo;{{ e($t->selection_text) }}&rdquo;</em></div>
              @endif

              <div style="margin-top:6px; color:#444;">{{ $latestBody }}</div>
            </div>
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

      // Treat the page *without* ?student as the shell. We only allow Export link
      // when both a real submission is bound AND ?student is present.
      $hasStudentQuery  = request()->has('student') && request('student') !== '';
      $canExportLink    = $isStaff && $hasSubmission && $hasStudentQuery && $exportStudentId;
      $hasContext       = $hasSubmission; // for enabling other staff buttons
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

      <button
  id="wk3-open-thread"
  class="btn"
  type="button"
  {{ $hasContext ? '' : 'disabled' }}
>
  Open Thread
</button>

      @if ($canExportLink)
        {{-- Real student page: render a real link --}}
        <a id="wk3-btn-export"
           class="btn"
           href="/workspace/{{ $type ?? 'essay' }}/export?student={{ $exportStudentId }}"
           style="text-decoration:none;color:inherit">
          Export
        </a>
      @else
        {{-- Shell or missing query: show disabled button with NO id (can’t be bound) --}}
        <button class="btn" type="button" disabled title="Open a student’s workspace from Dashboard to export">
          Export
        </button>
      @endif

    @else
      <!-- Student actions (always their own submission page) -->
      <button id="wk3-btn-history" class="btn" type="button">History</button>
      <button id="wk3-btn-request" class="btn" type="button">Request Feedback</button>
      {{-- Open Thread is staff-only; students use Request Feedback, which opens a thread --}}
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

  <!-- TipTap + App Logic -->
  <script type="module">
    import { Editor } from "https://esm.sh/@tiptap/core@2";
    import StarterKit from "https://esm.sh/@tiptap/starter-kit@2";
    import Image from "https://esm.sh/@tiptap/extension-image@2";
    import Link from "https://esm.sh/@tiptap/extension-link@2";

    // Elements & constants
    const el        = document.getElementById('wk3-editor');
    const TYPE      = "{{ $type ?? 'exhibition' }}";
    const ROLE      = "{{ $role ?? 'student' }}";
    const STUDENT_ID = {{ (int) $student->id }};
    const IS_STAFF  = (ROLE === 'teacher' || ROLE === 'admin');   // ← NEW: role flag
    const CSRF      = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const $status   = document.getElementById('wk3-status-label');
    const $savedAt  = document.getElementById('wk3-saved-at');
    const $list     = document.getElementById('wk3-thread-list');
    const $detail   = document.getElementById('wk3-thread-detail');
    const $sync     = document.getElementById('wk3-sync-pill');
    const $toast    = document.getElementById('wk3-toast');
    const $toastText= document.getElementById('wk3-toast-text');   // ← NEW: dynamic text
    const $toastBtn = document.getElementById('wk3-toast-reload');

    $toastBtn?.addEventListener('click', () => {
      // Force a clean rehydrate so the editor + rev are fresh
      const url = new URL(location.href);
      url.searchParams.set('ts', Date.now().toString()); // bust caches/CDN
      location.replace(url.toString());
    });

    // --- context gate ---
    const SUBMISSION  = el?.dataset.submissionId || "";
    const HAS_CONTEXT = !!SUBMISSION;   // ✅ true only when ?student=… and a submission exists


    // Reply UI
// Reply UI
const $replyBox    = document.getElementById('wk3-reply');
const $replyInput  = document.getElementById('wk3-reply-input');
const $replyBtn    = document.getElementById('wk3-reply-send');
const $replyClose  = document.getElementById('wk3-reply-close');
const $replyStat   = document.getElementById('wk3-reply-status'); // now null, harmless
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

    // Toast helpers
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

    // Double-click image → replace it
    function bindImageReplace(editor) {
      const pmView = editor?.view;
      if (!pmView) return;

      pmView.dom.addEventListener('dblclick', async (ev) => {
        if (!HAS_CONTEXT) return;
        const target = ev.target;
        if (!(target && target.nodeName === 'IMG')) return;

        // pick file
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = async () => {
          const file = input.files?.[0];
          if (!file) return;
          try {
            setStatus('Uploading image…'); setSync('Saving');
            const url = await uploadImageFile(file);

            // set selection to clicked image, then update attrs
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

    // --- client revision state (seeded from server) ---
    const initialRev = parseInt(el?.dataset.rev || '0', 10);
    let rev = Number.isFinite(initialRev) ? initialRev : 0;

    // Mount TipTap
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
          Image.configure({ inline: false, allowBase64: false }),
          Link.configure({
            autolink: true,
            linkOnPaste: true,
            openOnClick: false,  // prevents navigating when clicking links in edit mode
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

      // --- Dark mode toggle logic (final) ---
      const themeToggle = document.getElementById('wk3-theme-toggle');
      const editorWrap = document.querySelector('.editor-wrap');

      if (themeToggle && editorWrap) {
        // Click toggle between light/dark
        themeToggle.addEventListener('click', () => {
          const isDark = editorWrap.getAttribute('data-theme') === 'dark';
          const newTheme = isDark ? 'light' : 'dark';
          editorWrap.setAttribute('data-theme', newTheme);
          localStorage.setItem('wk3-editor-theme', newTheme);
        });

        // Load saved preference
        const savedTheme = localStorage.getItem('wk3-editor-theme');
        if (savedTheme) {
          editorWrap.setAttribute('data-theme', savedTheme);
        }
      }

      // --- OS theme sync (only if user hasn't chosen manually) ---
      const mq = window.matchMedia('(prefers-color-scheme: dark)');
      if (mq && editorWrap) {
        // If no explicit choice saved, follow OS theme on first load
        if (!localStorage.getItem('wk3-editor-theme')) {
          editorWrap.setAttribute('data-theme', mq.matches ? 'dark' : 'light');
        }

        // React to OS changes dynamically (but only if user hasn't chosen manually)
        mq.addEventListener?.('change', (e) => {
          if (!localStorage.getItem('wk3-editor-theme')) {
            editorWrap.setAttribute('data-theme', e.matches ? 'dark' : 'light');
          }
        });
      }

      ensureTrailingParagraph(editor);
      editor.on('update', () => ensureTrailingParagraph(editor));
      function ensureTrailingParagraph(ed) {
        // Skip entirely on read-only shells (no editing context)
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

      // --- WK3 Toolbar wiring ---
      const toolbar = document.querySelector('.wk3-toolbar');
      const imgBtn = document.getElementById('wk3-insert-image');
      const imgInput = document.getElementById('wk3-image-input');

      // When a file is chosen via the toolbar's hidden input, upload + insert it
      if (imgInput && editor) {
        imgInput.addEventListener('change', async () => {
          const file = imgInput.files && imgInput.files[0];
          if (!file) return;
          if (!HAS_CONTEXT) { imgInput.value = ''; return; } // safety

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
            imgInput.value = ''; // reset so selecting same file again still fires
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

        // --- Toolbar active/disabled state sync (visual + a11y) ---
        function qsCmd(cmd){ return toolbar?.querySelector(`.wk3-tool[data-cmd="${cmd}"]`) || null; }

        const btns = {
          undo:        qsCmd('undo'),
          redo:        qsCmd('redo'),
          bold:        qsCmd('bold'),
          italic:      qsCmd('italic'),
          strike:      qsCmd('strike'),
          heading1:    qsCmd('heading1'),
          heading2:    qsCmd('heading2'),
          paragraph:   qsCmd('paragraph'),
          bulletList:  qsCmd('bulletList'),
          orderedList: qsCmd('orderedList'),
          blockquote:  qsCmd('blockquote'),
          codeBlock:   qsCmd('codeBlock'),
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

          // Active styles
          setActive(btns.bold,        editor.isActive('bold'));
          setActive(btns.italic,      editor.isActive('italic'));
          setActive(btns.strike,      editor.isActive('strike'));
          setActive(btns.blockquote,  editor.isActive('blockquote'));
          setActive(btns.codeBlock,   editor.isActive('codeBlock'));
          setActive(btns.bulletList,  editor.isActive('bulletList'));
          setActive(btns.orderedList, editor.isActive('orderedList'));
          setActive(btns.paragraph,   editor.isActive('paragraph'));
          setActive(btns.heading1,    editor.isActive('heading', { level: 1 }));
          setActive(btns.heading2,    editor.isActive('heading', { level: 2 }));

          // Undo/redo availability
          setDisabled(btns.undo, !editor.can().undo());
          setDisabled(btns.redo, !editor.can().redo());
        }

        // Run on init and on every change that could affect formatting state
        if (editor) {
          syncToolbarState();
          editor.on('selectionUpdate', syncToolbarState);
          editor.on('transaction', syncToolbarState);
          editor.on('update', syncToolbarState);
        }

        // ✅ new feature: enable double-click replace
        bindImageReplace(editor);

        // --- Allow Cmd/Ctrl+Click to open links (safe edit-mode behaviour) ---
        editor?.view?.dom.addEventListener('click', (e) => {
          const a = e.target.closest?.('a[href]');
          if (!a) return;

          const href = a.getAttribute('href');
          if (!href) return;

          // In read-only shell: normal click opens.
          // In edit mode: require Cmd/Ctrl to open.
          const allowOpen = !HAS_CONTEXT || e.metaKey || e.ctrlKey;
          if (allowOpen) {
            e.preventDefault();
            window.open(href, '_blank', 'noopener,noreferrer');
          }
        });

        queueMicrotask(() => el.querySelector('.ProseMirror')?.focus());

        // expose for debugging
        // @ts-ignore
        window.wk3 = { editor, get rev() { return rev; } };
      }
    }

    // -------------------------
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

// --- Teacher unsaved snapshot guard (no autosave in shell) ---
let teacherDirty = false; // only meaningful when ROLE is staff AND HAS_CONTEXT === false

// Warn if teacher tries to leave with unsnapshotted edits
window.addEventListener('beforeunload', (e) => {
  const isStaff = (ROLE === 'teacher' || ROLE === 'admin');
  if (!isStaff) return;
  if (!teacherDirty) return;
  e.preventDefault();
  e.returnValue = ''; // required for Safari/Chrome to show the prompt
});

function scheduleAutosave() {
  if (!HAS_CONTEXT) return;
  if (IS_STAFF) return;                 // staff: never autosave (but UI can still update)
  if (autosaveTimer) clearTimeout(autosaveTimer);
  autosaveTimer = setTimeout(() => {
    setStatus(SAVING);
    setSync('Saving');
    saveDraft(false);
  }, AUTOSAVE_DELAY);
}

// --- New: always update the UI on change, but only students autosave
function handleDocChangeUI() {
  if (!HAS_CONTEXT) return;

  if (IS_STAFF) {
    // mark that the teacher has unsnapshotted edits in shell mode
    teacherDirty = true;
    markSnapshotGlow(true); // ← start subtle glow until snapshot
    setStatus('Editing (no autosave)');
    setSync('Unsaved (manual snapshot)');
    return;
  }

  if ($status && $status.textContent !== SAVING) setStatus(TYPING);
  setSync('Unsaved changes');
  scheduleAutosave();
}

if (editor) {
  // React to document changes
  editor.on('transaction', ({ transaction }) => {
    if (!transaction.docChanged) return;
    handleDocChangeUI();
  });

  // Save on blur (students only)
  editor.on('blur', () => {
    if (HAS_CONTEXT && !IS_STAFF && !saving) saveDraft(false);
  });
}

// Cmd/Ctrl+S saves immediately (staff => snapshot, student => draft)
document.addEventListener('keydown', (e) => {
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's') {
    e.preventDefault();
    if (!HAS_CONTEXT) return;                  // no context: swallow
    if (autosaveTimer) clearTimeout(autosaveTimer);
    setStatus(SAVING);
    setSync('Saving');
    saveDraft(IS_STAFF);                       // true=snapshot, false=draft
  }
});
    // Cmd/Ctrl+K → add/remove link
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

    // -------------------------
    // SAVE (serialized + 409 safe)
    // -------------------------
    async function saveDraft(isSnapshot = false) {
      clearTimeout(autosaveTimer); // prevent a second save from the pending debounce
      if (!editor) return;
      if (!HAS_CONTEXT) return;   // ← safety net should be here
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

        // --- Handle 409 Conflict gracefully ---
        if (res.status === 409) {
          let expected = null;
          try {
            const j = await res.json();
            expected = (typeof j?.expected === 'number') ? j.expected : null;
          } catch {}
          if (typeof expected === 'number') rev = expected;

          clearTimeout(autosaveTimer);
          autosaveTimer = setTimeout(() => saveDraft(false), AUTOSAVE_DELAY);

          setStatus('Conflict');
          setSync('Out of date');
          showToast('This page is out of date. Reload to get the latest version.');
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

// --- Step 3: clear unsaved flag after successful snapshot ---
if (isSnapshot) {
  teacherDirty = false;
  markSnapshotGlow(false);  // stop the glow when snapshot succeeds
}

      } catch (e) {
        console.error('[wk3] save error', e);
        setStatus('Error (network)');
        setSync('Error');

      } finally {
        saving = false;
        if (queued) { queued = false; saveDraft(false); }
      }
    } // ← end saveDraft()

    // --- network awareness (UX only; retry hook arrives in Step 4.2) ---
    let pendingAfterOnline = false;

    window.addEventListener('offline', () => {
      setStatus('Offline');
      setSync('Offline — changes queued');
      pendingAfterOnline = true;
    });

    window.addEventListener('online', () => {
      setStatus('Reconnected');
      setSync('Reconnected — saving…');

      // Auto-retry a save once we're online (if we have a real context)
      if (HAS_CONTEXT && editor && typeof saveDraft === 'function') {
        if (!saving) {
          saveDraft(false);          // fire immediately
        } else {
          queued = true;             // finish current save, then run another
        }
      }
    });

    window.saveDraft = saveDraft;

// -------------------------
// THREAD LIST / DETAIL
// -------------------------
function renderDetailLoading(id) {
  $detail.innerHTML = `<div class="td-head"><strong>Thread #${id}</strong><span class="td-meta">Loading…</span></div>`;
}

function renderDetailEmpty() {
  $detail.innerHTML = `<div class="td-empty">Select a thread to view messages here.</div>`;
  if ($replyBox) $replyBox.style.display = 'none';
  selectedThreadId = null;
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
  const head = `
    <div class="td-head">
      <div>
        <strong>Thread #${thread.id}</strong>
        ${thread.label ? `<span class="pill" style="margin-left:8px;">${thread.label}</span>` : ``}
      </div>
      <span class="td-meta">${thread.created_at || ''}</span>
    </div>
  `;

  const sel = thread.selection_text
    ? `<div class="sel"><em>&ldquo;${escapeHtml(thread.selection_text)}&rdquo;</em></div>`
    : ``;

  // Normalised current-user name (front/back spaces removed, case-insensitive)
  const meName = (window.AUTH_NAME || '').trim().toLowerCase();

  const msgs = (thread.messages || []).map(m => {
    const rawName = m.author?.name || m.from || 'User';
    const normName = rawName.trim().toLowerCase();

    const isMe  = meName && normName === meName;
    const rowCl = isMe ? 'td-msg-row from-me' : 'td-msg-row from-them';
    const msgCl = isMe ? 'td-msg from-me'     : 'td-msg from-them';

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

if ($list) {
  $list.addEventListener('click', async (ev) => {
    const card = ev.target.closest('.thread-card');
    if (!card) return;

    // Reset any previously active / hidden cards
    $list.querySelectorAll('.thread-card').forEach(el => {
      el.classList.remove('active');
      el.style.display = '';
    });

    // Mark current as active and hide its summary card
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
      } catch (e) { console.warn('[wk3] setTextSelection failed', e); }
    }

    try {
      renderDetailLoading(id);
      const res = await fetch(`/workspace/${TYPE}/thread/${id}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      if (!res.ok) {
        $detail.innerHTML = `<div class="td-empty">Couldn't load thread (#${id}).</div>`;
        if ($replyBox) $replyBox.style.display = 'none';
        console.error('[wk3] thread load failed', res.status, await res.text().catch(()=>'')); 
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
      if (!body) { setReplyStat('Type a message first.'); return; }

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
              headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
              credentials: 'same-origin',
            });
            const j = await again.json().catch(()=>null);
            if (j) renderDetail(j);
          }
        } else if (res.ok) {
          const again = await fetch(`/workspace/${TYPE}/thread/${selectedThreadId}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
          });
          const j = await again.json().catch(()=>null);
          if (j) renderDetail(j);
        } else {
          console.error('[wk3] reply failed', res.status, await res.text().catch(()=>'')); 
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

    // -------- Wire buttons (single IDs, role-aware) --------
    const $saveBtn = document.getElementById('wk3-btn-save');
    $saveBtn?.addEventListener('click', () => {
      const isStaff = (ROLE === 'teacher' || ROLE === 'admin');
      saveDraft(isStaff); // staff = snapshot; student = draft
    });

// --- Snapshot glow helper ---
function markSnapshotGlow(on = true) {
  const btn = document.getElementById('wk3-btn-save');
  if (!btn) return;
  btn.classList.toggle('glow', !!on);
}

const requestBtn    = document.getElementById('wk3-btn-request');
const openThreadBtn = document.getElementById('wk3-open-thread');

// Student “Request Feedback”
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

// Teacher “Open Thread”
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

    // Toggle thread pane visibility
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

    // --- History overlay logic ---
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
    // Same behaviour for now: just download/export that version
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

    // initial pill state
    setSync('In sync');
    
// --- Messages Panel (V3 with local seen-count) ---
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

// --- localStorage helpers: “how many messages have I seen for THIS submission?” ---
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
    // ignore storage errors (e.g. private mode)
  }
}

function updateMsgBadge(unread) {
  if (!msgBadge) return;
  const n = Number.isFinite(unread) ? unread : 0;

  if (n > 0) {
    msgBadge.textContent = String(n);
    msgBadge.style.display = 'inline-flex';
  } else {
    msgBadge.textContent = '';
    msgBadge.style.display = 'none';
  }
}

// Renders messages, but does NOT decide unread count any more
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
    // Opening panel = “I have now seen everything up to current total”
    loadMessages({ markSeen: true });
  }
}

// markSeen = true  → treat all current messages as read
// markSeen = false → just refresh list + badge, don’t change “seen” memory
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

    const data     = await res.json().catch(() => ({}));
    const messages = Array.isArray(data.messages) ? data.messages : [];
    const total    = messages.length;

    if (IS_STAFF) {
      // --- unread math with local memory (teachers/admins only) ---
      let seen = getSeenCount();

      // clamp weird cases (e.g. messages deleted)
      if (seen > total) seen = total;
      if (seen < 0)     seen = 0;

      // If we are explicitly opening the panel, consider everything “seen”
      if (markSeen) {
        seen = total;
      }

      setSeenCount(seen);

      const unread = Math.max(total - seen, 0);
      updateMsgBadge(unread);
    } else {
      // --- students: never show an unread badge for general messages ---
      // We still sync the counter so future math doesn't go negative/weird.
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

  // optimistic append
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
    // After sending, treat everything as read for this user
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

// Initial fetch so the unread badge is correct even before opening the panel
if (msgList && el?.dataset.submissionId) {
  loadMessages({ markSeen: false });
}
  </script>
</body>
</html>