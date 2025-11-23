@extends('tok_ls::layouts.ls')

@section('title', 'Class: ' . $class->name)

@section('tok_ls_breadcrumb')
    <a href="{{ route('tok-ls.student.home') }}">My Class</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <span>{{ $class->name }}</span>
@endsection

@section('content')

    <div class="tok-ls-class-header">
        <h1>Class: {{ $class->name }}</h1>
        <p class="tok-ls-subtitle">
            Select a lesson below to view instructions and submit your response.
        </p>
    </div>

    <section class="tok-ls-section tok-ls-section--lessons">
        <div class="tok-ls-section-header">
            <h2>Available Lessons</h2>
        </div>

        @if ($lessons->isEmpty())
            <p class="tok-ls-muted">
                There are no published lessons for this class yet.
                Please check again later.
            </p>
        @else
            <div class="tok-ls-lesson-grid">
                @foreach ($lessons as $lesson)
                    <a href="{{ route('tok-ls.student.lessons.respond', [$class->id, $lesson->id]) }}"
                       class="tok-ls-lesson-card">

                        <h3 class="tok-ls-lesson-title">
                            {{ $lesson->title }}
                        </h3>

                        @if ($lesson->published_at)
                            <p class="tok-ls-lesson-meta">
                                Published:
                                {{ $lesson->published_at->format('Y-m-d') }}
                            </p>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </section>

@endsection