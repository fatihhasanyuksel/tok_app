@extends('tok_ls::layouts.ls')

@section('title', 'Edit Lesson – ' . $class->name)

@section('tok_ls_breadcrumb')
    <a href="{{ route('tok-ls.teacher.classes') }}">Classes</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <a href="{{ route('tok-ls.teacher.classes.show', $class->id) }}">{{ $class->name }}</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <span>Edit Lesson</span>
@endsection

@section('content')

    {{-- ⭐ Header card, now inside tok-ls-section so width matches the form card --}}
    <section class="tok-ls-section">
        <div class="tok-ls-class-header">
            <h1>Edit Lesson</h1>
            <p class="tok-ls-subtitle">
                Update the title, objectives, success criteria, and content of this ToK Learning Space lesson.
            </p>

            @php
                $statusLabel = $lesson->status === 'published' ? 'Published' : 'Draft';
                $badgeBg     = $lesson->status === 'published' ? '#16a34a' : '#6b7280'; // green / gray
            @endphp

            <p class="tok-ls-lesson-meta" style="margin-top: 8px;">
                Status:
                <span
                    style="
                        display:inline-block;
                        padding:2px 10px;
                        border-radius:9999px;
                        font-size:0.8rem;
                        font-weight:600;
                        color:#ffffff;
                        background: {{ $badgeBg }};
                    "
                >
                    {{ $statusLabel }}
                </span>

                @if ($lesson->updated_at)
                    · Last updated:
                    {{ $lesson->updated_at->format('Y-m-d H:i') }}
                @endif
            </p>
        </div>
    </section>

    {{-- Existing form card – unchanged except for being after the new header card --}}
    <section class="tok-ls-section tok-ls-lesson-editor">

        <form method="POST"
              action="{{ route('tok-ls.teacher.lessons.update', [$class->id, $lesson->id]) }}"
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
                    value="{{ old('title', $lesson->title) }}"
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
                    value="{{ old('duration_minutes', $lesson->duration_minutes) }}"
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
                >{{ old('objectives', $lesson->objectives) }}</textarea>
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
                >{{ old('success_criteria', $lesson->success_criteria) }}</textarea>
                @error('success_criteria')
                    <p class="tok-ls-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Content (TipTap-ready) --}}
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
                    >{{ old('content', $lesson->content ?? '') }}</textarea>
                </div>

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