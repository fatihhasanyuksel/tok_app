@extends('tok_ls::layouts.ls')

@section('tok_ls_breadcrumb')
    <a href="{{ route('tok-ls.teacher.templates.index') }}">Lesson Library</a>
@endsection

@section('content')

    {{-- Header Section --}}
    <div class="tok-header-container">
        <h3 class="tok-ls-page-title">Your Classes</h3>

        <div style="display:flex; gap:12px; align-items:center;">
            <a href="{{ route('tok-ls.teacher.classes.archived') }}" class="tok-btn-secondary">
                View Archived Classes
            </a>

            <a href="{{ route('tok-ls.teacher.classes.create') }}" class="tok-btn-primary">
                + Create New Class
            </a>
        </div>
    </div>

    {{-- Content Section --}}
    @if ($classes->isEmpty())
        <div class="tok-empty-state">
            <h3>No classes created yet</h3>
            <p>Get started by clicking the "Create New Class" button above.</p>
        </div>
    @else
        <div class="tok-ls-grid">
            @foreach ($classes as $class)
                {{-- Whole card is now a link --}}
                <a href="{{ route('tok-ls.teacher.classes.show', $class->id) }}"
                   class="tok-ls-card">

                    <h3 class="tok-ls-card-title">
                        {{ $class->name }}
                    </h3>

                </a>
            @endforeach
        </div>
    @endif

@endsection