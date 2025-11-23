@extends('tok_ls::layouts.ls')

@section('title', 'Lesson: ' . $lesson->title)

@section('tok_ls_breadcrumb')
    <a href="{{ route('tok-ls.student.home') }}">My Class</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <span>{{ $lesson->title }}</span>
@endsection

@section('content')

    <div class="tok-ls-class-header">
        <h1>{{ $lesson->title }}</h1>
        <p class="tok-ls-subtitle">
            Class: {{ $class->name }}
        </p>
    </div>

    {{-- Box 1: Lesson content --}}
    <section class="tok-ls-section">
        <h2>Lesson Content</h2>

        @if ($lesson->content)
            <div class="tok-ls-lesson-content">
                {!! nl2br(e($lesson->content)) !!}
            </div>
        @else
            <p class="tok-ls-muted">No lesson content has been added yet.</p>
        @endif
    </section>

    {{-- Box 2: Student response --}}
    <section class="tok-ls-section">
        <h2>Your Response</h2>

        <form method="POST"
              action="{{ route('tok-ls.student.lessons.save-response', [$class->id, $lesson->id]) }}"
              class="tok-ls-form"
        >
            @csrf

            <div class="tok-ls-form-group">
                <label for="student_response" class="tok-ls-label">Write your response</label>
                <textarea
                    id="student_response"
                    name="student_response"
                    rows="6"
                    class="tok-ls-textarea"
                >{{ old('student_response', $response->student_response ?? '') }}</textarea>
                @error('student_response')
                    <p class="tok-ls-error">{{ $message }}</p>
                @enderror
            </div>

            <p class="tok-ls-muted" id="tok-ls-autosave-status">
                <!-- Filled by JS -->
            </p>

            <div class="tok-ls-form-actions">
                <button type="submit" class="tok-ls-btn tok-ls-btn--primary">
                    Save response
                </button>
            </div>
        </form>
    </section>

    {{-- Box 3: Teacher feedback --}}
    <section class="tok-ls-section">
        <h2>Teacher Feedback</h2>

        @if ($response && $response->teacher_feedback)
            <div class="tok-ls-feedback-box">
                {!! nl2br(e($response->teacher_feedback)) !!}
            </div>
        @else
            <p class="tok-ls-muted">
                No feedback has been added yet.
            </p>
        @endif
    </section>

    {{-- Lightweight autosave --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form      = document.querySelector('.tok-ls-form');
            if (!form) return;

            const textarea  = document.getElementById('student_response');
            const statusEl  = document.getElementById('tok-ls-autosave-status');
            const url       = form.getAttribute('action');
            const tokenEl   = form.querySelector('input[name="_token"]');

            if (!textarea || !statusEl || !url || !tokenEl) return;

            const token = tokenEl.value;
            let lastValue = textarea.value;
            let timerId   = null;

            function setStatus(text) {
                statusEl.textContent = text;
            }

            function scheduleAutosave() {
                if (timerId) {
                    clearTimeout(timerId);
                }

                timerId = setTimeout(function () {
                    // No change since last save → skip
                    if (textarea.value === lastValue) {
                        return;
                    }

                    lastValue = textarea.value;

                    const formData = new FormData();
                    formData.append('_token', token);
                    formData.append('student_response', textarea.value);

                    fetch(url, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                        .then(function () {
                            setStatus('Autosaved just now.');
                        })
                        .catch(function () {
                            setStatus('Autosave failed (possibly offline).');
                        });
                }, 1500); // 1.5s after last keystroke
            }

            textarea.addEventListener('input', scheduleAutosave);

            // Initial message
            if (textarea.value.trim() !== '') {
                setStatus('All changes saved.');
            } else {
                setStatus('Start typing – your work will autosave.');
            }
        });
    </script>

@endsection