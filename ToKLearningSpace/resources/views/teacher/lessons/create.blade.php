@extends('tok_ls::layouts.ls')

@section('title', 'Create Lesson for ' . $class->name)

@section('tok_ls_breadcrumb')
    <a href="{{ route('tok-ls.teacher.classes') }}">Classes</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <a href="{{ route('tok-ls.teacher.classes.show', $class->id) }}">{{ $class->name }}</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <span>Create Lesson</span>
@endsection

@section('content')

    @php
        // Safely derive initial values from template (if any), but let old() override
        $initialTitle      = old('title',            isset($template) && $template ? $template->title            : '');
        $initialDuration   = old('duration_minutes', isset($template) && $template ? $template->duration_minutes : '');
        $initialObjectives = old('objectives',       isset($template) && $template ? $template->objectives       : '');
        $initialCriteria   = old('success_criteria', isset($template) && $template ? $template->success_criteria : '');
        $initialContent    = old('content',          isset($template) && $template ? $template->content_html     : '');
        $currentStatus     = old('status', 'draft');
    @endphp

    <div class="tok-ls-class-header">
        <h1>Create Lesson for {{ $class->name }}</h1>
        <p class="tok-ls-subtitle">
            Add the title, objectives, success criteria, and content of your ToK lesson.
        </p>

        @if (!empty($template))
            <p class="tok-ls-lesson-meta">
                Using template: <strong>{{ $template->title }}</strong>
            </p>
        @endif
    </div>

    <section class="tok-ls-section tok-ls-lesson-editor">

        <form method="POST"
              action="{{ route('tok-ls.teacher.lessons.store', $class->id) }}"
              class="tok-ls-form">
            @csrf

            {{-- Title --}}
            <div class="tok-ls-form-group">
                <label for="title" class="tok-ls-label">Lesson Title</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    class="tok-ls-input"
                    value="{{ $initialTitle }}"
                    placeholder="Enter lesson title..."
                    required
                >
                @error('title')
                    <p class="tok-ls-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Estimated Duration (minutes) --}}
            <div class="tok-ls-form-group">
                <label for="duration_minutes" class="tok-ls-label">
                    Estimated Duration (minutes)
                </label>
                <input
                    type="number"
                    id="duration_minutes"
                    name="duration_minutes"
                    class="tok-ls-input tok-ls-input-small"
                    min="0"
                    step="5"
                    value="{{ $initialDuration }}"
                >
                @error('duration_minutes')
                    <p class="tok-ls-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Objectives --}}
            <div class="tok-ls-form-group">
                <label for="objectives" class="tok-ls-label">Objectives</label>
                <textarea
                    id="objectives"
                    name="objectives"
                    class="tok-ls-textarea"
                    rows="3"
                    placeholder="What should students know / understand / be able to do by the end of this lesson?"
                >{{ $initialObjectives }}</textarea>
                @error('objectives')
                    <p class="tok-ls-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Success Criteria --}}
            <div class="tok-ls-form-group">
                <label for="success_criteria" class="tok-ls-label">Success Criteria</label>
                <textarea
                    id="success_criteria"
                    name="success_criteria"
                    class="tok-ls-textarea"
                    rows="3"
                    placeholder="How will students (and you) know they have been successful?"
                >{{ $initialCriteria }}</textarea>
                @error('success_criteria')
                    <p class="tok-ls-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Content (TipTap via RichEditor.vue) --}}
            <div class="tok-ls-form-group">
                <label for="content" class="tok-ls-label">Lesson Content</label>

                <div
                    data-tok-ls-rich-editor
                    data-tok-ls-upload-endpoint="{{ route('tok-ls.teacher.lesson-images.upload') }}"
                    data-tok-ls-can-upload="1"
                >
                    <textarea
                        id="content"
                        name="content"
                        class="tok-ls-textarea"
                        rows="8"
                        data-tok-ls-input
                    >{{ $initialContent }}</textarea>
                </div>

                @error('content')
                    <p class="tok-ls-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status --}}
            <div class="tok-ls-form-group">
                <label for="status" class="tok-ls-label">Status</label>
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