@php
  // If a thread is resolved, force the UI to "Resolved" regardless of internal status.
  $resolved = (bool) ($thread->is_resolved ?? false);

  // Internal status (used only when not resolved)
  $st = strtolower($thread->status ?? 'open');

  // Shared palette
  $colorMap = [
    'open'     => ['#e8f1ff', '#0a2e6c'], // blue
    'seen'     => ['#e8f1ff', '#0a2e6c'], // treat like open
    'revised'  => ['#fff4e5', '#8a5a00'], // amber
    'approved' => ['#e6ffed', '#135f26'], // green
    'closed'   => ['#e6ffed', '#135f26'], // treat like approved/green
  ];

  // Internal -> UI label + which color-key to borrow (used when NOT resolved)
  $uiMap = [
    'open'     => ['Awaiting Student', 'open'],
    'seen'     => ['Awaiting Student', 'open'],
    'revised'  => ['Awaiting Teacher', 'revised'],
    'closed'   => ['Resolved',         'closed'],
    'approved' => ['Resolved',         'closed'],
  ];

  if ($resolved) {
      // Force green "Resolved"
      $label   = 'Resolved';
      [$bg, $fg] = $colorMap['closed'];
  } else {
      [$label, $colorKey] = $uiMap[$st] ?? [ucfirst($st), $st];
      [$bg, $fg]          = $colorMap[$colorKey] ?? ['#f6f8ff', '#334'];
  }

  // Keep parameter handling consistent with workspace
  $studentIdParam  = request('student') ?? ($student->id ?? null);
  $routeParamsBase = ['type' => $type] + ($studentIdParam ? ['student' => $studentIdParam] : []);

  $isTeacher = in_array(optional(Auth::user())->role, ['teacher','admin']);
@endphp

<div
  x-data="(typeof threadPane === 'function')
            ? threadPane({
                pollUrl:   '{{ route('thread.poll',   ['type'=>$type,'thread'=>$thread->id] + $routeParamsBase) }}',
                typingUrl: '{{ route('thread.typing', ['type'=>$type,'thread'=>$thread->id] + $routeParamsBase) }}'
              })
            : {}"
  x-init="typeof startPolling === 'function' && startPolling()"
  @cleanup-thread.window="typeof stop === 'function' && stop()"
>
  <div class="thread-card">
    <div class="thread-hdr">
      <div>
        <div style="font-weight:600">
          Feedback
          @if(!empty($thread->selection_text))
            <span class="muted">on “{{ \Illuminate\Support\Str::limit($thread->selection_text, 60) }}”</span>
          @endif
          <span class="muted">· {{ $thread->created_at?->diffForHumans() }}</span>
        </div>
        @if(!empty($thread->selection_text))
          <div class="sel"><em>“{{ \Illuminate\Support\Str::limit($thread->selection_text, 140) }}”</em></div>
        @endif
      </div>

      <!-- ✅ Status pill + (teacher-only) Resolve action -->
      <div style="display:flex; align-items:center; gap:8px;">
        <span class="status-pill" style="background:{{ $bg }}; color:{{ $fg }}; border-color:{{ $bg }}">
          {{ $label }}
        </span>

        @if($isTeacher && !$resolved)
          <form method="POST"
                action="{{ route('thread.resolve', ['type' => $type, 'thread' => $thread->id] + $routeParamsBase) }}"
                style="display:inline;">
            @csrf
            <button type="submit"
              class="px-3 py-1 rounded-full text-sm font-medium text-white bg-green-600 hover:bg-green-700 transition">
              Resolve
            </button>
          </form>
        @endif
      </div>
    </div>

    {{-- Messages (live-updated by poll if threadPane is present) --}}
    <div class="bubble-list" id="bubbleList">
      @include('partials.thread_messages', ['thread' => $thread, 'student' => $student])
    </div>

    {{-- Reply (plain POST; no Alpine required) --}}
    <form method="POST"
          action="{{ route('thread.reply', ['type'=>$type,'thread'=>$thread->id] + $routeParamsBase) }}"
          style="margin-top:10px"
          autocomplete="off">
      @csrf
      <textarea class="input" name="body" rows="3" placeholder="Write a reply…" required></textarea>

      <div style="display:flex; gap:8px; align-items:center; margin-top:8px;">
        <button type="submit" class="btn sm">Send</button>

        {{-- Close switches the right pane back to the list.
             Works both with and without a parent event listener. --}}
        <button type="button"
                class="btn secondary sm"
                @click="
                  // Preferred: tell the parent pane to show the list
                  $dispatch('hybrid-list');
                  // Fallback: flip parent state directly if available
                  (window.hyPane ? (window.hyPane.mode='list', window.hyPane.current=null) : null);
                ">
          Close
        </button>

        {{-- Optional typing note (only shows if threadPane provided it) --}}
        <span class="muted"
              x-text="typeof typingNote !== 'undefined' ? typingNote : ''"
              style="margin-left:auto;"
              x-show="typeof typingNote !== 'undefined' && typingNote"></span>
      </div>
    </form>
  </div>
</div>