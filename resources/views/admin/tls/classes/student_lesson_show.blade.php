@extends('layout')

@section('head')
    {{-- Re-use the same admin CSS as the main dashboard --}}
    <link rel="stylesheet" href="{{ asset('tok-admin/css/tok-admin-dashboard.css') }}">
@endsection

@section('content')
    <div class="tok-admin-shell">

        {{-- Back to lessons list --}}
        <p style="margin-bottom: 12px;">
            <a href="{{ route('tok-ls.admin.classes.student-lessons', [$class->id, $student->id]) }}" class="btn">
                ← Back to Lessons
            </a>
        </p>

        {{-- Class + Student header --}}
        <div class="card" style="margin-bottom: 16px;">
            <div class="card-header">
                <div class="card-header-title">
                    Lesson view — {{ $class->name }} / {{ $student->name }}
                </div>
            </div>
            <div class="card-body">
                <p>
                    <strong>Class:</strong> {{ $class->name }}
                </p>
                <p>
                    <strong>Student:</strong> {{ $student->name }}
                </p>
                <p style="font-size:13px; color:#6b7280;">
                    Admin read-only view of lesson content and the student's response.
                </p>
            </div>
        </div>

        {{-- Lesson content --}}
        <div class="card" style="margin-bottom: 16px;">
            <div class="card-header card-header-muted">
                <div class="card-header-title">
                    Lesson: {{ $lesson->title ?? 'Untitled Lesson' }}
                </div>
            </div>
            <div class="card-body">
                <div style="font-size:14px;">
                    {!! $lesson->content ?? $lesson->body ?? '' !!}
                </div>
            </div>
        </div>

        {{-- Student response --}}
        <div class="card">
            <div class="card-header card-header-muted">
                <div class="card-header-title">
                    Student Response
                </div>
            </div>
            <div class="card-body">
                @if ($response)
                    @if ($response->updated_at)
                        <p style="font-size:12px; color:#6b7280; margin-bottom:8px;">
                            Last updated: {{ $response->updated_at->format('Y-m-d H:i') }}
                        </p>
                    @endif

                    <div style="font-size:14px;">
                        {!! $response->content ?? $response->body ?? '' !!}
                    </div>
                @else
                    <p class="empty-state-text">
                        This student has not submitted a response for this lesson yet.
                    </p>
                @endif
            </div>
        </div>

    </div>
@endsection