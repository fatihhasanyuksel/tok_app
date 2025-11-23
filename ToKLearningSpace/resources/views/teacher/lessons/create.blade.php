@extends('tok_ls::layouts.ls')

@section('title', 'Create Lesson – ' . $class->name)

@section('tok_ls_breadcrumb')
    <a href="{{ route('tok-ls.teacher.classes') }}">Classes</a>
    <span class="tok-ls-breadcrumb-separator">›</span>

    <a href="{{ route('tok-ls.teacher.classes.show', $class->id) }}">
        {{ $class->name }}
    </a>

    <span class="tok-ls-breadcrumb-separator">›</span>
    <span>Create Lesson</span>
@endsection

@section('content')

    <div class="tok-ls-class-header">
        <h1>Create Lesson for {{ $class->name }}</h1>
        <p class="tok-ls-subtitle">
            Add the title and content of your ToK lesson.
        </p>
    </div>

    <section class="tok-ls-section">

        <form method="POST"
              action="{{ route('tok-ls.teacher.lessons.store', $class->id) }}"
              class="tok-ls-form">

            @csrf

            {{-- Lesson Title --}}
            <div class="tok-ls-form-group">
                <label for="title" class="tok-ls-label">Lesson Title</label>
                <input
                    id="title"
                    type="text"
                    name="title"
                    class="tok-ls-input"
                    placeholder="Enter lesson title..."
                    value="{{ old('title') }}"
                    required
                >
                @error('title')
                    <p class="tok-ls-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Lesson Content (prepared for RichEditor / TipTap) --}}
            <div class="tok-ls-form-group">
                <label for="content" class="tok-ls-label">Lesson Content</label>

                {{-- LS rich editor mount point --}}
                <div data-tok-ls-rich-editor>
                    <textarea
                        id="content"
                        name="content"
                        class="tok-ls-textarea"
                        rows="10"
                        data-tok-ls-input
                        placeholder="Write your lesson content here..."
                    >{{ old('content') }}</textarea>
                </div>

                <p class="tok-ls-field-hint">
                    Students will see this content on their lesson page.
                </p>

                @error('content')
                    <p class="tok-ls-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status --}}
            <div class="tok-ls-form-group">
                <label for="status" class="tok-ls-label">Status</label>
                <select id="status" name="status" class="tok-ls-input">
                    <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>
                        Draft
                    </option>
                    <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>
                        Published
                    </option>
                </select>
                @error('status')
                    <p class="tok-ls-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="tok-ls-form-actions">
                <button type="submit"
                        class="tok-ls-btn tok-ls-btn--primary">
                    Save Lesson
                </button>

                <a href="{{ route('tok-ls.teacher.classes.show', $class->id) }}"
                   class="tok-ls-btn tok-ls-btn--ghost">
                    Cancel
                </a>
            </div>

        </form>

    </section>

@endsection