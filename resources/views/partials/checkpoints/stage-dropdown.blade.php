{{-- STAGE-PARTIAL-MARKER v2 --}}
{{-- resources/views/partials/checkpoints/stage-dropdown.blade.php --}}
@php
    use Illuminate\Support\Carbon;

    // Normalize inputs
    $studentId       = $studentId       ?? null;
    $workType        = $workType        ?? null;
    $currentStageKey = $currentStageKey ?? null;
    $currentLabel    = $currentLabel    ?? null;

    // New (optional) meta
    $updatedAt       = $updatedAt       ?? null;   // timestamp string or Carbon
    $updatedBy       = $updatedBy       ?? null;   // teacher name (string)

    // Ensure we have an iterable stages list (no DB calls here)
    $stages = collect($stages ?? []);

    // Unique element id so the inline script can bind safely
    $selectId = 'checkpoint-stage-' . e($workType) . '-' . e($studentId);

    // Resolve the currently selected label if not provided
    if ($currentLabel === null && $currentStageKey !== null) {
        $match = $stages->firstWhere('key', $currentStageKey);
        $currentLabel = $match->label ?? null;
    }

    // Friendly placeholder text
    $placeholder = $currentLabel ? "Currently: {$currentLabel}" : 'Select stage…';

    // Format updated meta (if available)
    $updatedHuman = $updatedAt
        ? ( $updatedAt instanceof Carbon ? $updatedAt : Carbon::parse($updatedAt) )->diffForHumans()
        : null;
@endphp

@if ($stages->isEmpty())
    <span class="muted">No stages configured</span>
@else
    <select
        id="{{ $selectId }}"
        class="checkpoint-stage"
        data-student="{{ $studentId }}"
        data-work="{{ $workType }}"
        aria-label="Update {{ $workType }} checkpoint stage"
        style="padding:6px 8px; font-size:13px; max-width: 220px;"
    >
        {{-- Placeholder / current --}}
        <option value="" {{ $currentStageKey ? '' : 'selected' }} disabled hidden>
            {{ $placeholder }}
        </option>

        @foreach ($stages as $stage)
            <option
                value="{{ $stage->key }}"
                {{ $currentStageKey === $stage->key ? 'selected' : '' }}
            >
                {{ $stage->label }}
            </option>
        @endforeach
    </select>

    {{-- Tiny last-updated line (only if meta provided) --}}
    @if($updatedHuman)
        <div class="muted" style="font-size:12px; margin-top:4px;">
            Updated {{ $updatedHuman }}@if($updatedBy) by {{ $updatedBy }}@endif
        </div>
    @endif

    <script>
    (function () {
        const sel = document.getElementById(@json($selectId));
        if (!sel) return;

        if (sel.dataset.bound === '1') return;
        sel.dataset.bound = '1';

        const postUrl = @json(route('checkpoints.status.update'));
        const csrf    = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        sel.addEventListener('change', async function () {
            const stageKey  = sel.value;
            const studentId = sel.dataset.student;
            const workType  = sel.dataset.work;

            const prevOutline = sel.style.outline;
            sel.style.outline = '2px solid rgba(0,0,0,0.15)';

            try {
                const res = await fetch(postUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        student_id: Number(studentId),
                        work_type: String(workType),
                        stage_key: String(stageKey)
                    }),
                    credentials: 'same-origin'
                });

                if (!res.ok) {
                    throw new Error('Update failed with status ' + res.status);
                }

                sel.style.outline = '2px solid rgba(46, 204, 113, 0.6)';
                setTimeout(() => { sel.style.outline = prevOutline; }, 900);
            } catch (err) {
                console.error(err);
                sel.style.outline = '2px solid rgba(231, 76, 60, 0.6)';
                setTimeout(() => { sel.style.outline = prevOutline; }, 1200);
                alert('Could not update stage. Please try again.');
            }
        });
    })();
    </script>
@endif