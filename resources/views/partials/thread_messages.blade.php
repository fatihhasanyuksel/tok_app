{{-- renders the chat bubbles for a thread; expects $thread and $student --}}

@php
  $messages    = $thread->messages ?? collect();
  $lastId      = $messages->count() ? (int) $messages->last()->id : 0;

  // Be defensive: $student should be a User; support both id and email just in case.
  $studentId    = isset($student) ? ($student->id ?? null)    : null;
  $studentEmail = isset($student) ? ($student->email ?? null) : null;
@endphp

<div id="bubbleInner" data-last-id="{{ $lastId }}">
  @forelse($messages as $m)
    @php
      $authorId    = $m->author_id ?? null;
      $authorEmail = $m->author->email ?? null;

      // Student if author matches student's id OR email (covers any rare mapping mismatch)
      $isStudent = false;
      if (!is_null($studentId) && !is_null($authorId) && (string)$authorId === (string)$studentId) {
        $isStudent = true;
      } elseif ($studentEmail && $authorEmail && strcasecmp($authorEmail, $studentEmail) === 0) {
        $isStudent = true;
      }

      $role        = $isStudent ? 'student' : 'teacher';
      $displayName = $m->author->name ?? ucfirst($role);
      $when        = optional($m->created_at)->diffForHumans();
    @endphp

    <div class="row {{ $role }}">
      <div class="bubble {{ $role }}" data-author-role="{{ $role }}">
        <div style="white-space:pre-wrap">{{ $m->body }}</div>
        <div class="bubble-meta">
          <span class="muted">{{ $displayName }}</span>
          @if($when)
            &nbsp;•&nbsp; {{ $when }}
          @endif
        </div>
      </div>
    </div>
  @empty
    <div class="muted">No messages yet.</div>
  @endforelse
</div>

{{-- ─────────────────────────────────────────────────────────────────────────────
  Tiny UI polish:
  - Prevent double-submits on this thread’s reply form
  - Show a small spinner while posting
  This attaches to any <form> whose action ends with "/thread/{id}/reply"
────────────────────────────────────────────────────────────────────────────── --}}
<style>
  .tok-spinner {
    display:inline-block;
    width:14px; height:14px;
    border:2px solid currentColor;
    border-right-color: transparent;
    border-radius:50%;
    margin-right:6px;
    vertical-align:-2px;
    animation: tok-spin .7s linear infinite;
  }
  @keyframes tok-spin { to { transform: rotate(360deg); } }
  .tok-sending { opacity: .75; pointer-events: none; }
</style>

<script>
(function() {
  // Make sure we only bind once per partial render
  const threadId = {{ (int) $thread->id }};
  const boundFlag = `tokBoundReply_${threadId}`;
  if (window[boundFlag]) return;
  window[boundFlag] = true;

  // Find the reply form for THIS thread (action contains ".../thread/{id}/reply")
  const selector = `form[action$="/thread/${threadId}/reply"], form[action*="/thread/${threadId}/reply"]`;
  const form = document.querySelector(selector);
  if (!form) return; // reply form might be elsewhere in DOM on first paint

  const submitBtn = form.querySelector('button[type="submit"], [type="submit"].btn, .btn[type="submit"]');

  form.addEventListener('submit', function(e) {
    // Basic guard: avoid empty body double-posts if browser didn't enforce required
    const bodyInput = form.querySelector('textarea[name="body"], [name="body"]');
    if (bodyInput && typeof bodyInput.value === 'string' && bodyInput.value.trim() === '') {
      // Let native validation handle it if 'required' is set; otherwise prevent
      e.preventDefault();
      bodyInput.focus();
      return;
    }

    if (submitBtn && !submitBtn.disabled) {
      submitBtn.disabled = true;
      submitBtn.classList.add('tok-sending');

      // Keep original label to restore if needed
      if (submitBtn.dataset.origLabel == null) {
        submitBtn.dataset.origLabel = submitBtn.innerHTML;
      }

      // Spinner + label
      submitBtn.innerHTML = `<span class="tok-spinner" aria-hidden="true"></span>Sending…`;

      // Safety re-enable in case of client-side navigation errors (won’t run on normal redirect)
      setTimeout(() => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.classList.remove('tok-sending');
          if (submitBtn.dataset.origLabel) submitBtn.innerHTML = submitBtn.dataset.origLabel;
        }
      }, 8000);
    }
  }, { once: false });
})();
</script>