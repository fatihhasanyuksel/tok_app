@extends('tok_ls::layouts.ls')

@section('content')

    <h1 class="tok-ls-page-title">ToK Learning Space – Your Classes</h1>

    <p>
        <a href="{{ route('tok-ls.teacher.classes.create') }}">+ Create New Class</a>
    </p>

    @if ($classes->isEmpty())
        <p>No classes created yet.</p>
    @else
        <ul class="tok-ls-class-list">
            @foreach ($classes as $class)
                <li class="tok-ls-class-item" style="display:flex;align-items:center;gap:12px;">

                    {{-- Open class --}}
                    <a href="{{ route('tok-ls.teacher.classes.show', $class->id) }}">
                        {{ $class->name }}
                    </a>

                    {{-- Delete Class --}}
                    <form action="{{ route('tok-ls.teacher.classes.destroy', $class->id) }}"
                          method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this class? This cannot be undone.');"
                          style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="color:red;">
                            Delete
                        </button>
                    </form>

                </li>
            @endforeach
        </ul>
    @endif

@endsection