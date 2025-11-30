@extends('tok_ls::layouts.ls')

@section('title', 'Feedback — ' . $lesson->title)

@section('tok_ls_breadcrumb')
    <a href="{{ route('tok-ls.teacher.classes') }}">Classes</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <a href="{{ route('tok-ls.teacher.classes.show', $class->id) }}">{{ $class->name }}</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <a href="{{ route('tok-ls.teacher.lessons.show', [$class->id, $lesson->id]) }}">{{ $lesson->title }}</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <span>Response: {{ $student->name ?? 'Student' }}</span>
@endsection

@section('content')

    <div class="tok-ls-class-header">
        <h1>Response: {{ $student->name ?? 'Student' }}</h1>
        <p class="tok-ls-subtitle">
            Lesson: <strong>{{ $lesson->title }}</strong>
            · Class: <strong>{{ $class->name }}</strong>
        </p>

        <p>
            <a href="{{ route('tok-ls.teacher.lessons.show', [$class->id, $lesson->id]) }}">
                ← Back to lesson
            </a>
        </p>
    </div>

    {{-- Card 1: Student response --}}
    <section class="tok-ls-section">
        <h2>Student Response</h2>

        @if (!empty($response->student_response))
            <div class="tok-ls-lesson-content">
                {!! $response->student_response !!}
            </div>
        @else
            <p class="tok-ls-muted">No response has been submitted yet.</p>
        @endif
    </section>

    {{-- Card 2: Teacher feedback editor --}}
    <section class="tok-ls-section tok-ls-feedback-editor">
        <h2>Your Feedback</h2>

        <form method="POST"
              action="{{ route('tok-ls.teacher.lessons.responses.feedback', [$class->id, $lesson->id, $response->id]) }}"
              class="tok-ls-form">
            @csrf

            <div class="tok-ls-form-group">
                <label for="teacher_feedback" class="tok-ls-label">Write feedback</label>

                <div
                    data-tok-ls-rich-editor
                    data-tok-ls-upload-endpoint="{{ route('tok-ls.teacher.lesson-images.upload') }}"
                    data-tok-ls-can-upload="1"
                >
                    <textarea
                        id="teacher_feedback"
                        name="teacher_feedback"
                        class="tok-ls-textarea"
                        rows="8"
                        data-tok-ls-input
                    >{{ old('teacher_feedback', $response->teacher_feedback ?? '') }}</textarea>
                </div>

                @error('teacher_feedback')
                    <p class="tok-ls-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="tok-ls-form-actions">
                <button type="submit" class="tok-ls-btn tok-ls-btn--primary">
                    Save feedback
                </button>

                <a href="{{ route('tok-ls.teacher.lessons.show', [$class->id, $lesson->id]) }}"
                   class="tok-ls-btn tok-ls-btn--ghost">
                    Cancel
                </a>
            </div>
        </form>
    </section>

@endsection