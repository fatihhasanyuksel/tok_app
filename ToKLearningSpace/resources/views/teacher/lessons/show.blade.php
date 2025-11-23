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

    {{-- Flash message --}}
    @if (session('success'))
        <div class="tok-ls-alert tok-ls-alert--success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Page header --}}
    <div class="tok-ls-class-header">
        <h1>Lesson: {{ $lesson->title }}</h1>

        <p class="tok-ls-subtitle">
            Class: <strong>{{ $class->name }}</strong>
        </p>

        <p class="tok-ls-subtitle">
            Status:
            <strong>{{ $lesson->published_at ? 'Published' : 'Draft' }}</strong>
            @if ($lesson->published_at)
                · Published: {{ $lesson->published_at->format('Y-m-d') }}
            @else
                · Not published yet
            @endif
        </p>

        <p class="tok-ls-subtitle">
            <a href="{{ route('tok-ls.teacher.lessons.edit', [$class->id, $lesson->id]) }}">
                Edit Lesson
            </a>
            ·
            <a href="{{ route('tok-ls.teacher.classes.show', $class->id) }}">
                Back to Class
            </a>
        </p>

        <form method="POST"
              action="{{ route('tok-ls.teacher.lessons.toggle-publish', [$class->id, $lesson->id]) }}"
              class="tok-ls-inline-form">
            @csrf
            <button type="submit" class="tok-ls-btn tok-ls-btn--tiny">
                {{ $lesson->published_at ? 'Unpublish Lesson' : 'Publish Lesson' }}
            </button>
        </form>
    </div>

    {{-- Lesson content --}}
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

    {{-- Student responses overview --}}
    <section class="tok-ls-section">
        <h2>Student Responses</h2>

        @if ($responses->isEmpty())
            <p class="tok-ls-muted">No student responses yet.</p>
        @else
            <div class="tok-ls-table-wrapper">
                <table class="tok-ls-table"
                       style="width:100%; border-collapse:collapse; font-size:0.95rem;">
                    <thead>
                        <tr>
                            <th style="text-align:left; padding:0.5rem 0.75rem;">Student</th>
                            <th style="text-align:left; padding:0.5rem 0.75rem;">Status</th>
                            <th style="text-align:left; padding:0.5rem 0.75rem;">Preview</th>
                            <th style="text-align:left; padding:0.5rem 0.75rem;">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($responses as $response)
                            @php
                                $preview = $response->student_response
                                    ? \Illuminate\Support\Str::limit(strip_tags($response->student_response), 70)
                                    : null;
                            @endphp
                            <tr>
                                <td style="padding:0.5rem 0.75rem;">
                                    {{ $response->student->name ?? $response->student->email }}
                                </td>

                                <td style="padding:0.5rem 0.75rem;">
                                    @if ($response->student_response)
                                        <span class="tok-ls-tag tok-ls-tag--green">Submitted</span>
                                    @else
                                        <span class="tok-ls-tag tok-ls-tag--gray">No response</span>
                                    @endif
                                </td>

                                <td style="padding:0.5rem 0.75rem;">
                                    @if ($preview)
                                        {{ $preview }}
                                    @else
                                        <span class="tok-ls-muted">No preview available.</span>
                                    @endif
                                </td>

                                <td style="padding:0.5rem 0.75rem;">
                                    @if ($response->student_response)
                                        {{-- Clicking this expands the inline feedback panel via ?student=ID --}}
                                        <a href="{{ route('tok-ls.teacher.lessons.show', [
                                                $class->id,
                                                $lesson->id,
                                                'student' => $response->student_id
                                            ]) }}"
                                           class="tok-ls-link-action">
                                            View &amp; Feedback
                                        </a>
                                    @else
                                        <span class="tok-ls-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Detail + feedback panel for selected student --}}
    @if ($selectedStudent)
        <section class="tok-ls-section">
            <div class="tok-ls-section-header">
                <h2>
                    Response from {{ $selectedStudent->name ?? $selectedStudent->email }}
                </h2>

                {{-- Collapse / close: go back to plain list (no ?student=) --}}
                <a href="{{ route('tok-ls.teacher.lessons.show', [$class->id, $lesson->id]) }}"
                   class="tok-ls-link-action">
                    Close
                </a>
            </div>

            @if ($selectedResponse)
                <div class="tok-ls-lesson-content tok-ls-response-full">
                    {!! nl2br(e($selectedResponse->student_response)) !!}
                </div>

                <form method="POST"
                      action="{{ route('tok-ls.teacher.lessons.responses.feedback', [
                          $class->id,
                          $lesson->id,
                          $selectedResponse->id
                      ]) }}"
                      class="tok-ls-form">
                    @csrf

                    <div class="tok-ls-form-group">
                        <label for="teacher_feedback" class="tok-ls-label">
                            Teacher Feedback
                        </label>
                        <textarea
                            id="teacher_feedback"
                            name="teacher_feedback"
                            rows="5"
                            class="tok-ls-textarea"
                        >{{ old('teacher_feedback', $selectedResponse->teacher_feedback ?? '') }}</textarea>
                    </div>

                    <div class="tok-ls-form-actions">
                        <button type="submit" class="tok-ls-btn tok-ls-btn--primary">
                            Save feedback
                        </button>
                    </div>
                </form>
            @else
                <p class="tok-ls-muted">
                    This student has not submitted a response yet.
                </p>
            @endif
        </section>
    @endif

@endsection