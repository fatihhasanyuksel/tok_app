@extends('tok_ls::layouts.ls')

@section('title', 'Add Students to ' . $class->name)

@section('tok_ls_breadcrumb')
    <a href="{{ route('tok-ls.teacher.classes') }}">Classes</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <a href="{{ route('tok-ls.teacher.classes.show', $class->id) }}">{{ $class->name }}</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <span>Add Students</span>
@endsection

@section('content')

    <div class="tok-ls-box">

        <h1 class="tok-ls-page-title">Add Students to {{ $class->name }}</h1>
        <p class="tok-ls-subtitle">
            Search your ToK students and add them to this class. Logic for attaching will come in the next step.
        </p>

        {{-- 🔍 Search bar (real, but still simple) --}}
        <form method="GET"
              action="{{ route('tok-ls.teacher.classes.students.add', $class->id) }}"
              class="tok-ls-student-search">
            <label for="tok-ls-search" class="tok-ls-label">Search by name or email</label>
            <div class="tok-ls-search-row">
                <input
                    id="tok-ls-search"
                    type="text"
                    name="q"
                    value="{{ $search ?? '' }}"
                    placeholder="Type to filter students..."
                    class="tok-ls-input"
                >
                <button type="submit" class="tok-ls-btn">
                    Search
                </button>
            </div>
        </form>

        {{-- 📋 Results area --}}
        <div class="tok-ls-student-results">
            <h2 class="tok-ls-section-title">Available Students</h2>

            @if ($students->isEmpty())
                <p class="tok-ls-muted">
                    @if (!empty($search))
                        No students found matching "<strong>{{ $search }}</strong>".
                    @else
                        No ToK students available to add yet.
                    @endif
                </p>
            @else

                {{-- 🔹 POST form wrapping the list --}}
                <form method="POST"
                      action="{{ route('tok-ls.teacher.classes.students.store', $class->id) }}">
                    @csrf

                    <ul class="tok-ls-student-list">
                        @foreach ($students as $student)
                            <li class="tok-ls-student-item">
                                <label class="tok-ls-student-row">
                                    <input
                                        type="checkbox"
                                        name="student_ids[]"
                                        value="{{ $student->id }}"
                                        class="tok-ls-checkbox"
                                    >
                                    <span class="tok-ls-student-name">{{ $student->name }}</span>
                                    <span class="tok-ls-student-email">{{ $student->email }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tok-ls-actions">
                        <button type="submit" class="tok-ls-btn">
                            Add selected students to {{ $class->name }}
                        </button>
                    </div>

                </form>
            @endif

        </div>

    </div>

@endsection