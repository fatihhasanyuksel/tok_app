@extends('tok_ls::layouts.ls')

@section('title', 'Lesson: ' . $lesson->title)

@section('tok_ls_breadcrumb')
    <a href="{{ route('tok-ls.teacher.classes') }}">Classes</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <a href="{{ route('tok-ls.teacher.classes.show', $class->id) }}">{{ $class->name }}</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <span>{{ $lesson->title }}</span>
@endsection

@section('content')

    <div class="tok-ls-class-header">
        <h1>Lesson: {{ $lesson->title }}</h1>
        <p class="tok-ls-subtitle">
            Class: <strong>{{ $class->name }}</strong>
            @if(!is_null($lesson->duration_minutes))
                — Estimated duration: {{ $lesson->duration_minutes }} min
            @endif
        </p>

        <p class="tok-ls-subtitle">
            Status:
            <strong>{{ $lesson->status === 'published' ? 'Published' : 'Draft' }}</strong>
            @if($lesson->published_at)
                · Published: {{ $lesson->published_at->format('Y-m-d') }}
            @endif
        </p>

        <p>
            <a href="{{ route('tok-ls.teacher.lessons.edit', [$class->id, $lesson->id]) }}">Edit Lesson</a>
            ·
            <a href="{{ route('tok-ls.teacher.classes.show', $class->id) }}">Back to Class</a>
        </p>

        {{-- ⭐ Publish + Save as Template — side by side --}}
        <div style="display:flex; gap:8px; margin-top:8px;">

            {{-- Publish / Unpublish --}}
            <form method="POST"
                  action="{{ route('tok-ls.teacher.lessons.toggle-publish', [$class->id, $lesson->id]) }}">
                @csrf
                <button type="submit" class="tok-ls-btn tok-ls-btn--tiny">
                    {{ $lesson->status === 'published' ? 'Unpublish Lesson' : 'Publish Lesson' }}
                </button>
            </form>

            {{-- Save this lesson as a template --}}
            <form method="POST"
                  action="{{ route('tok-ls.teacher.templates.store-from-lesson', [$class->id, $lesson->id]) }}">
                @csrf
                <button type="submit" class="tok-ls-btn tok-ls-btn--tiny">
                    Save as Template
                </button>
            </form>

        </div>
    </div>

    {{-- Card 1: Lesson Overview --}}
    <section class="tok-ls-section">
        <h2>Lesson Overview</h2>

        @if(!is_null($lesson->duration_minutes))
            <p class="tok-ls-lesson-meta">
                <strong>Estimated duration:</strong> {{ $lesson->duration_minutes }} min
            </p>
        @endif

        @if (!empty($lesson->objectives))
            <h3>Objectives</h3>
            <p>{!! nl2br(e($lesson->objectives)) !!}</p>
        @endif

        @if (!empty($lesson->success_criteria))
            <h3>Success Criteria</h3>
            <p>{!! nl2br(e($lesson->success_criteria)) !!}</p>
        @endif
    </section>

    {{-- Card 2: Lesson Content --}}
    <section class="tok-ls-section">
        <h2>Lesson Content</h2>

        @if ($lesson->content)
            <div class="tok-ls-lesson-content">
                {!! $lesson->content !!}
            </div>
        @else
            <p class="tok-ls-muted">No lesson content has been added yet.</p>
        @endif
    </section>

    {{-- Card 3: Student responses --}}
    <section class="tok-ls-section">
        <h2>Student Responses</h2>

        @if ($responses->isEmpty())
            <p class="tok-ls-muted">No student responses yet.</p>
        @else
            <table class="tok-ls-table">
                <thead>
                <tr>
                    <th>Student</th>
                    <th>Last updated</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($responses as $response)
                    <tr>
                        <td>{{ $response->student->name ?? 'Unknown student' }}</td>
                        <td>{{ $response->updated_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>
                            <a href="{{ route('tok-ls.teacher.lessons.responses.show', [$class->id, $lesson->id, $response->id]) }}">
                                View / Give feedback
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>

@endsection