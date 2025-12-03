@extends('layout')

@section('head')
    {{-- Re-use the same admin CSS as the main dashboard --}}
    <link rel="stylesheet" href="{{ asset('tok-admin/css/tok-admin-dashboard.css') }}">
@endsection

@section('content')
    <div class="tok-admin-shell">

        {{-- Back to class --}}
        <p style="margin-bottom: 12px;">
            <a href="{{ route('tok-ls.admin.classes.show', $class->id) }}" class="btn">
                ← Back to Class
            </a>
        </p>

        {{-- Header --}}
        <div class="card" style="margin-bottom: 16px;">
            <div class="card-header">
                <div class="card-header-title">
                    Lessons in {{ $class->name }} — {{ $student->name }}
                </div>
            </div>
            <div class="card-body">
                <p>
                    <strong>Student:</strong> {{ $student->name }}
                </p>
                <p style="font-size:13px; color:#6b7280;">
                    Admin read-only view of this student's ToK Learning Space lessons.
                </p>
            </div>
        </div>

        {{-- Lessons list --}}
        <div class="card">
            <div class="card-header card-header-muted">
                <div class="card-header-title">
                    Lessons in this class
                </div>
            </div>

            <div class="card-body">
                @if ($lessons->isEmpty())
                    <p class="empty-state-text">
                        No lessons assigned to this class.
                    </p>
                @else
                    <ul style="list-style:none; margin:0; padding:0;">
                        @foreach ($lessons as $lesson)
                            <li style="border-bottom:1px solid #f3f4f6; padding:10px 0;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <div style="font-weight:600;">
                                            {{ $lesson->title ?? 'Untitled Lesson' }}
                                        </div>
                                        @if ($lesson->created_at)
                                            <div style="font-size:12px; color:#6b7280;">
                                                Created: {{ $lesson->created_at->format('Y-m-d H:i') }}
                                            </div>
                                        @endif
                                    </div>

                                    <div>
                                        <a href="{{ route('tok-ls.admin.classes.student-lesson-show', [$class->id, $student->id, $lesson->id]) }}"
                                           class="btn btn-sm">
                                            View
                                        </a>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    </div>
@endsection