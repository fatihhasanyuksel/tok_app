@extends('tok_ls::layouts.ls')

@section('title', 'Lessons – ' . $class->name)

@section('tok_ls_breadcrumb')
    <a href="{{ route('tok-ls.teacher.classes') }}">Classes</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <a href="{{ route('tok-ls.teacher.classes.show', $class->id) }}">{{ $class->name }}</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <span>Lessons</span>
@endsection

@section('content')

    <div class="tok-ls-class-header">
        <h1>Lessons for {{ $class->name }}</h1>
        <p class="tok-ls-subtitle">
            Create and manage ToK Learning Space lessons for this class.
        </p>
    </div>

    <section class="tok-ls-section">
        <p>
            <a href="{{ route('tok-ls.teacher.lessons.create', $class->id) }}"
               class="tok-ls-link-action">
                + Create New Lesson
            </a>
        </p>

        @if ($lessons->isEmpty())
            <p class="tok-ls-muted">No lessons created for this class yet.</p>
        @else
            <ul class="tok-ls-lesson-list">
                @foreach ($lessons as $lesson)
                    <li class="tok-ls-lesson-item">

                        {{-- Main lesson link --}}
                        <a href="{{ route('tok-ls.teacher.lessons.show', [$class->id, $lesson->id]) }}"
                           class="tok-ls-lesson-link">
                            <span class="tok-ls-lesson-title">{{ $lesson->title }}</span>

                            @if ($lesson->status === 'draft')
                                <span class="tok-ls-badge tok-ls-badge--muted">Draft</span>
                            @elseif ($lesson->status === 'published')
                                <span class="tok-ls-badge tok-ls-badge--success">Published</span>
                            @else
                                <span class="tok-ls-badge">{{ ucfirst($lesson->status) }}</span>
                            @endif
                        </a>

                        {{-- NEW: Edit link --}}
                        <span class="tok-ls-lesson-actions" style="margin-left:10px;">
                            <a href="{{ route('tok-ls.teacher.lessons.edit', [$class->id, $lesson->id]) }}"
                               class="tok-ls-link-action">
                                Edit
                            </a>
                        </span>

                    </li>
                @endforeach
            </ul>
        @endif
    </section>

@endsection