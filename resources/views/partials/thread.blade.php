@php
    // ── Status pill: Resolved / Awaiting Student / Awaiting Teacher ─────────────

    $resolved = (bool) ($thread->is_resolved ?? false);

    // Prefer already-loaded messages (controller loads messages.author:id,role,name)
    if ($thread->relationLoaded('messages')
        && $thread->messages instanceof \Illuminate\Support\Collection
        && $thread->messages->count()) {

        $lastMsg = $thread->messages->sortBy('created_at')->last();

        // Ensure author relation is here (defensive)
        if ($lastMsg && !$lastMsg->relationLoaded('author')) {
            $lastMsg->loadMissing('author:id,role,name');
        }

        $lastRole = strtolower(optional(optional($lastMsg)->author)->role ?? '');

    } else {
        // Fallback: fetch one last message with author role
        $lastMsg = \App\Models\CommentMessage::with('author:id,role,name')
            ->where('comment_id', $thread->id)
            ->orderByDesc('created_at')
            ->first();

        $lastRole = strtolower(optional(optional($lastMsg)->author)->role ?? '');
    }

    if ($resolved) {
        $label = 'Resolved';
        [$bg, $fg] = ['#e6ffed', '#135f26']; // green
    } else {
        if (in_array($lastRole, ['teacher', 'admin'], true)) {
            // Teacher/admin spoke last → student’s turn
            $label = 'Awaiting Student';
            [$bg, $fg] = ['#e8f1ff', '#0a2e6c']; // blue
        } else {
            // Student (or nobody) spoke last → teacher’s turn
            $label = 'Awaiting Teacher';
            [$bg, $fg] = ['#fff4e5', '#8a5a00']; // amber
        }
    }

    // Route/role helpers used by the template below
    $studentIdParam  = request('student') ?? ($student->id ?? null);
    $routeParamsBase = ['type' => $type] + ($studentIdParam ? ['student' => $studentIdParam] : []);
    $isTeacher       = in_array(optional(Auth::user())->role, ['teacher','admin'], true);
@endphp

<div
  class="thread"
  data-thread-id="{{ $thread->id }}"
  data-pm-from="{{ $thread->pm_from ?? '' }}"
  data-pm-to="{{ $thread->pm_to ?? '' }}"
  data-start-offset="{{ $thread->start_offset ?? '' }}"
  data-end-offset="{{ $thread->end_offset ?? '' }}"
  x-data="(typeof threadPane === 'function')
            ? threadPane({
                pollUrl:   '{{ route('thread.poll',   ['type'=>$type,'thread'=>$thread->id] + $routeParamsBase) }}',
                typingUrl: '{{ route('thread.typing', ['type'=>$type,'thread'=>$thread->id] + $routeParamsBase) }}'
              })
            : {}"
  x-init="typeof startPolling === 'function' && startPolling()"
  @cleanup-thread.window="typeof stop === 'function' && stop()"
>
  <div class="thread-card" data-thread-id="{{ $thread->id }}">

    {{-- HEADER: Status + Resolve --}}
    <div class="thread-hdr" style="margin-bottom:10px;">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:6px;">
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

        <div class="muted" style="font-size:12px;">
          {{ $thread->created_at?->diffForHumans() }}
        </div>
      </div>

      {{-- Yellow quote box (hover-to-highlight wiring is already in workspace) --}}
      @if(!empty($thread->selection_text))
        <div class="sel" data-thread-id="{{ $thread->id }}" style="margin:0;">
          <em>“{{ \Illuminate\Support\Str::limit($thread->selection_text, 420) }}”</em>
        </div>
      @endif
    </div>

    {{-- Messages --}}
    <div class="bubble-list" id="bubbleList">
      @include('partials.thread_messages', ['thread' => $thread, 'student' => $student])
    </div>

    {{-- Reply --}}
    <form method="POST"
          action="{{ route('thread.reply', ['type'=>$type,'thread'=>$thread->id] + $routeParamsBase) }}"
          style="margin-top:10px"
          autocomplete="off">
      @csrf
      <textarea class="input" name="body" rows="3" placeholder="Write a reply…" required></textarea>

      <div style="display:flex; gap:8px; align-items:center; margin-top:8px;">
        <button type="submit" class="btn sm">Send</button>
        <button type="button"
                class="btn secondary sm"
                @click="
                  $dispatch('hybrid-list');
                  (window.hyPane ? (window.hyPane.mode='list', window.hyPane.current=null) : null);
                ">
          Close
        </button>

        <span class="muted"
              x-text="typeof typingNote !== 'undefined' ? typingNote : ''"
              style="margin-left:auto;"
              x-show="typeof typingNote !== 'undefined' && typingNote"></span>
      </div>
    </form>
  </div>
</div>