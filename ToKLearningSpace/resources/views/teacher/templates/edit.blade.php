@extends('tok_ls::layouts.ls')

@section('title', 'Edit Template — Lesson Library')

@section('content')
    <div class="tok-ls-page">
        <h1 class="tok-ls-page-title">Edit Lesson Template</h1>

        <p class="tok-ls-page-intro">
            Update the title, objectives, success criteria, and content of this lesson template.
        </p>

        @if (session('success'))
            <div class="tok-ls-alert tok-ls-alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="tok-ls-alert tok-ls-alert-error">
                <p><strong>There were some problems with your input:</strong></p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Quick status line --}}
        <div class="tok-ls-meta-row">
            <span class="tok-ls-badge">
                {{ $template->is_published ? 'Published' : 'Draft' }}
            </span>
            <span class="tok-ls-meta">
                Last updated: {{ $template->updated_at?->format('Y-m-d H:i') ?? '—' }}
            </span>
        </div>

        {{-- Update form --}}
        <form method="POST"
              action="{{ route('tok-ls.teacher.templates.update', $template->id) }}"
              class="tok-ls-form">

            @csrf
            @method('PUT')

            {{-- Title --}}
            <div class="tok-ls-field">
                <label for="title" class="tok-ls-label">Title *</label>
                <input type="text"
                       id="title"
                       name="title"
                       class="tok-ls-input"
                       value="{{ old('title', $template->title) }}"
                       required>
            </div>

            {{-- Topic --}}
            <div class="tok-ls-field">
                <label for="topic" class="tok-ls-label">Topic</label>
                <input type="text"
                       id="topic"
                       name="topic"
                       class="tok-ls-input"
                       value="{{ old('topic', $template->topic) }}">
            </div>

            {{-- Estimated Duration (minutes) --}}
            <div class="tok-ls-field tok-ls-field-inline">
                <label for="duration_minutes" class="tok-ls-label">
                    Estimated Duration (minutes)
                </label>
                <input type="number"
                       id="duration_minutes"
                       name="duration_minutes"
                       class="tok-ls-input tok-ls-input-small"
                       min="0"
                       step="5"
                       value="{{ old('duration_minutes', $template->duration_minutes) }}">
            </div>

            {{-- Objectives --}}
            <div class="tok-ls-field">
                <label for="objectives" class="tok-ls-label">Objectives</label>
                <textarea id="objectives"
                          name="objectives"
                          class="tok-ls-textarea"
                          rows="4">{{ old('objectives', $template->objectives) }}</textarea>
            </div>

            {{-- Success Criteria --}}
            <div class="tok-ls-field">
                <label for="success_criteria" class="tok-ls-label">Success Criteria</label>
                <textarea id="success_criteria"
                          name="success_criteria"
                          class="tok-ls-textarea"
                          rows="4">{{ old('success_criteria', $template->success_criteria) }}</textarea>
            </div>

            {{-- Lesson Content (HTML) — TipTap-based --}}
            <div class="tok-ls-field">
                <label for="content_html" class="tok-ls-label">Lesson Content</label>

                <div
                    data-tok-ls-rich-editor
                    data-tok-ls-upload-endpoint="{{ route('tok-ls.teacher.template-images.upload') }}"
                    data-tok-ls-can-upload="1"
                >
                    <textarea id="content_html"
                              name="content_html"
                              class="tok-ls-textarea"
                              rows="8"
                              data-tok-ls-input>{{ old('content_html', $template->content_html) }}</textarea>
                </div>

                <p class="tok-ls-help-text">
                    This editor stores clean HTML in the template so you can reuse it across classes.
                </p>
            </div>

            {{-- Notes --}}
            <div class="tok-ls-field">
                <label for="notes" class="tok-ls-label">Notes</label>
                <textarea id="notes"
                          name="notes"
                          class="tok-ls-textarea"
                          rows="3">{{ old('notes', $template->notes) }}</textarea>
            </div>

            {{-- Publish checkbox --}}
            <div class="tok-ls-field tok-ls-field-inline">
                <label class="tok-ls-checkbox-label">
                    <input type="checkbox"
                           name="is_published"
                           value="1"
                           {{ old('is_published', $template->is_published) ? 'checked' : '' }}>
                    Publish this template in the library
                </label>
            </div>

            <div class="tok-ls-form-actions">
                <button type="submit" class="tok-ls-btn tok-ls-btn-primary">
                    Save Changes
                </button>

                <a href="{{ route('tok-ls.teacher.templates.index') }}"
                   class="tok-ls-btn tok-ls-btn-link">
                    Back to Library
                </a>
            </div>
        </form>

        {{-- Danger zone: delete --}}
        <div class="tok-ls-danger-zone">
            <h2 class="tok-ls-danger-title">Delete Template</h2>
            <p class="tok-ls-danger-text">
                Deleting this template will remove it from the Lesson Library.
                Existing class lessons that were created from it will NOT be affected.
            </p>

            <form method="POST"
                  action="{{ route('tok-ls.teacher.templates.destroy', $template->id) }}"
                  onsubmit="return confirm('Are you sure you want to delete this template from the library? This cannot be undone.');">
                @csrf
                @method('DELETE')

                <button type="submit" class="tok-ls-btn tok-ls-btn-danger">
                    Delete Template
                </button>
            </form>
        </div>
    </div>
@endsection