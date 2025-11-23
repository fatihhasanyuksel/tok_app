@extends('tok_ls::layouts.ls')

@section('content')

    <h1 class="tok-ls-page-title">Create a New Class</h1>

    <form action="{{ route('tok-ls.teacher.classes.store') }}" method="POST" class="tok-ls-form">
        @csrf

        <label class="tok-ls-label">
            Class Name
            <input type="text" name="name" required class="tok-ls-input">
        </label>

        <button type="submit" class="tok-ls-button">Create Class</button>
    </form>

    <p style="margin-top:20px;">
        <a href="{{ route('tok-ls.teacher.classes') }}">← Back to Classes</a>
    </p>

@endsection