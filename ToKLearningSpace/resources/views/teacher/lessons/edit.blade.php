@extends('tok_ls::layouts.ls')

@section('title', 'Edit Lesson – ' . $class->name)

@section('tok_ls_breadcrumb')
    <a href="{{ route('tok-ls.teacher.classes') }}">Classes</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <a href="{{ route('tok-ls.teacher.classes.show', $class->id) }}">{{ $class->name }}</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <a href="{{ route('tok-ls.teacher.lessons.index', $class->id) }}">Lessons</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <span>Edit Lesson</span>
@endsection

@section('content')

    <div class="tok-ls-class-header">
        <h1>Edit Lesson</h1>
        <p class="tok-ls-subtitle">
            Update the details of this ToK Learning Space lesson.
        </p>
    </div>

    <section class="tok-ls-section">

        <form method="POST"
              action="{{ route('tok-ls.teacher.lessons.update', [$class->id, $lesson->id]) }}"
              class="tok-ls-form">
            @csrf

            {{-- Title --}}
            <div class="tok-ls-form-group">
                <label for="title" class="tok-ls-label">Lesson title</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    class="tok-ls-input"
                    value="{{ old('title', $lesson->title) }}"
                    required
                >
                @error('title')
                    <p class="tok-ls-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Content --}}
            <div class="tok-ls-form-group">
                <label for="content" class="tok-ls-label">Lesson Content</label>
                <textarea
                    id="content"
                    name="content"
                    class="tok-ls-textarea"
                    rows="8"
                >{{ old('content', $lesson->content) }}</textarea>
                @error('content')
                    <p class="tok-ls-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status --}}
            <div class="tok-ls-form-group">
                <label for="status" class="tok-ls-label">Status</label>
                @php
                    $currentStatus = old('status', $lesson->status ?? 'draft');
                @endphp
                <select id="status" name="status" class="tok-ls-select">
                    <option value="draft" {{ $currentStatus === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ $currentStatus === 'published' ? 'selected' : '' }}>Published</option>
                </select>
                @error('status')
                    <p class="tok-ls-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="tok-ls-form-actions">
                <button type="submit" class="tok-ls-btn tok-ls-btn--primary">
                    Save changes
                </button>

                <a href="{{ route('tok-ls.teacher.lessons.show', [$class->id, $lesson->id]) }}"
                   class="tok-ls-btn tok-ls-btn--ghost">
                    Cancel
                </a>
            </div>
        </form>

    </section>

@endsection