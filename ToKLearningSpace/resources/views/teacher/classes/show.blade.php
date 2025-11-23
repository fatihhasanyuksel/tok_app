@extends('tok_ls::layouts.ls')

@section('title', 'Class: ' . $class->name)

@section('tok_ls_breadcrumb')
    {{-- This section is styled by the CSS to appear at the top of the page --}}
    <a href="{{ route('tok-ls.teacher.classes') }}">Classes</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <span>{{ $class->name }}</span>
@endsection

@section('content')

    {{-- Page header (Not wrapped in .tok-ls-section) --}}
    <div class="tok-ls-class-header">
        <h1>Class: {{ $class->name }}</h1>
        <p class="tok-ls-subtitle">
            Manage students, lessons, and view analytics.
        </p>
    </div>

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

            <a href="{{ route('tok-ls.teacher.lessons.create', $class->id) }}"
               class="tok-ls-link-action">
                + Create Lesson
            </a>
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