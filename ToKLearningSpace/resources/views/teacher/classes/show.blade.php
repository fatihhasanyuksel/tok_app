@extends('tok_ls::layouts.ls')

@section('title', 'Class: ' . $class->name)

@section('tok_ls_breadcrumb')
    {{-- This section is styled by the CSS to appear at the top of the page --}}
    <a href="{{ route('tok-ls.teacher.classes') }}">Classes</a>
    <span class="tok-ls-breadcrumb-separator">&gt;</span>
    <span>{{ $class->name }}</span>
@endsection

@section('content')

    {{-- Page header (Not wrapped in .tok-ls-section) --}}
    <div class="tok-ls-class-header">
        <h1>Class: {{ $class->name }}</h1>
        <p class="tok-ls-subtitle">
            Manage students, lessons, and view analytics.
        </p>

        <div style="display: flex; gap: 8px; margin-top: 8px;">
            {{-- Archive (soft hide) --}}
            <form method="POST"
                  action="{{ route('tok-ls.teacher.classes.archive', $class->id) }}"
                  class="tok-ls-inline-form"
                  onsubmit="return confirm('Archive this class? Students and lessons will be hidden but NOT deleted.');">
                @csrf
                <button type="submit"
                        class="tok-ls-btn tok-ls-btn--tiny">
                    Archive Class
                </button>
            </form>

            {{-- Hard Delete (permanent) --}}
            <form method="POST"
                  action="{{ route('tok-ls.teacher.classes.destroy', $class->id) }}"
                  class="tok-ls-inline-form"
                  onsubmit="return confirm('Permanently delete this class and all its lessons/responses? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="tok-ls-btn tok-ls-btn--tiny"
                        style="color:#ef4444;">
                    Delete Class
                </button>
            </form>
        </div>
    </div>

    {{-- ARCHIVED BANNER (read-only indicator) --}}
    @if ($class->isArchived())
        <div style="
            margin-top: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            background: #fef3c7;
            border: 1px solid #fbbf24;
            color: #92400e;
            font-size: 0.9rem;
        ">
            This class is currently <strong>archived</strong>. It is hidden from your main class list
            and from students. You can still view its content here, or unarchive it from the
            <a href="{{ route('tok-ls.teacher.classes.archived') }}" class="tok-ls-link-action">
                Archived Classes
            </a>
            page.
        </div>
    @endif

    {{-- Students (single card) --}}
    <section class="tok-ls-section">
        <h2>Students</h2>

        <p>
            <a href="{{ route('tok-ls.teacher.classes.students.add', $class->id) }}"
               class="tok-ls-link-action">
                + Add Students
            </a>
        </p>

        @if ($class->students->isEmpty())
            <p class="tok-ls-muted">No students have been added to this class yet.</p>
        @else
            <ul class="tok-ls-student-list">
                @foreach ($class->students as $student)
                    <li class="tok-ls-student-item">
                        {{ $student->name ?? $student->email }}

                        <form method="POST"
                              action="{{ route('tok-ls.teacher.classes.students.remove', [$class->id, $student->id]) }}"
                              class="tok-ls-inline-form">
                            @csrf
                            <button type="submit" class="tok-ls-btn tok-ls-btn--tiny">
                                Remove
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Lessons / Units (edX-style responsive grid) --}}
    @php
        // Reverted: Simple collection retrieval without extra logic
        $lessons = $class->relationLoaded('lessons')
            ? $class->lessons
            : $class->lessons()->orderBy('created_at', 'desc')->get();
    @endphp

    <section class="tok-ls-section tok-ls-section--lessons">
        <div class="tok-ls-section-header">
            <h2>Lessons / Units</h2>

            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                {{-- Go to Lesson Library WITH class context --}}
                <a href="{{ route('tok-ls.teacher.templates.index', ['class' => $class->id]) }}"
                   class="tok-ls-link-action">
                    Lesson Library
                </a>

                {{-- Create a new lesson in this class --}}
                <a href="{{ route('tok-ls.teacher.lessons.create', $class->id) }}"
                   class="tok-ls-link-action">
                    + Create Lesson
                </a>
            </div>
        </div>

        @if ($lessons->isEmpty())
            <p class="tok-ls-muted">
                No lessons created for this class yet.
                Click <strong>+ Create Lesson</strong> to add the first one.
            </p>
        @else
            <div class="tok-ls-lesson-grid">
                @foreach ($lessons as $lesson)
                    <a href="{{ route('tok-ls.teacher.lessons.show', [$class->id, $lesson->id]) }}"
                       class="tok-ls-lesson-card">

                        <h3 class="tok-ls-lesson-title">
                            {{ $lesson->title }}
                        </h3>

                        <p class="tok-ls-lesson-meta">
                            Status:
                            <strong>{{ ucfirst($lesson->status ?? 'draft') }}</strong>
                        </p>

                        @if ($lesson->published_at)
                            <p class="tok-ls-lesson-meta">
                                Published: {{ $lesson->published_at->format('Y-m-d') }}
                            </p>
                        @else
                            <p class="tok-ls-lesson-meta tok-ls-muted">
                                Not published yet
                            </p>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Analytics placeholder (single card again) --}}
    <section class="tok-ls-section">
        <h2>Analytics (coming soon)</h2>
        <p class="tok-ls-muted">
            We will display class-level metrics here.
        </p>
    </section>

    {{-- JAVASCRIPT BLOCK REMOVED --}}

@endsection