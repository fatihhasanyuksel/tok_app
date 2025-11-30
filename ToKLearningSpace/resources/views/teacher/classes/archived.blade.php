@extends('tok_ls::layouts.ls')

@section('title', 'Archived Classes')

@section('tok_ls_breadcrumb')
    {{-- Breadcrumb: back to active classes --}}
    <a href="{{ route('tok-ls.teacher.classes') }}">Classes</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <span>Archived</span>
@endsection

@section('content')

    {{-- Page header --}}
    <div class="tok-ls-class-header">
        <h1>Archived Classes</h1>
        <p class="tok-ls-subtitle">
            These classes are hidden from normal use but kept for reference and auditing.
        </p>

        <div style="margin-top: 8px;">
            <a href="{{ route('tok-ls.teacher.classes') }}"
               class="tok-ls-link-action">
                ← Back to Active Classes
            </a>
        </div>
    </div>

    {{-- Archived classes list --}}
    <section class="tok-ls-section">
        <h2>Archived Class List</h2>

        @if ($classes->isEmpty())
            <p class="tok-ls-muted">
                There are no archived classes at the moment.
            </p>
        @else
            <ul class="tok-ls-student-list">
                @foreach ($classes as $class)
                    <li class="tok-ls-student-item">

                        {{-- Class name + archive timestamp --}}
                        <div>
                            <strong>{{ $class->name }}</strong>

                            @if ($class->archived_at)
                                <span class="tok-ls-muted">
                                    — archived {{ $class->archived_at->format('Y-m-d H:i') }}
                                </span>
                            @endif
                        </div>

                        {{-- UNARCHIVE BUTTON --}}
                        <form method="POST"
                              action="{{ route('tok-ls.teacher.classes.unarchive', $class->id) }}"
                              class="tok-ls-inline-form"
                              onsubmit="return confirm('Restore this class? It will reappear in the active class list.');">
                            @csrf
                            <button type="submit"
                                    class="tok-ls-btn tok-ls-btn--tiny">
                                Unarchive
                            </button>
                        </form>

                    </li>
                @endforeach
            </ul>
        @endif
    </section>

@endsection