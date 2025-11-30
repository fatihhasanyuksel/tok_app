@extends('tok_ls::layouts.ls')

@section('title', 'Lesson Library')

@section('tok_ls_breadcrumb')
    <a href="{{ route('tok-ls.teacher.classes') }}">Classes</a>
    <span class="tok-ls-breadcrumb-separator">›</span>
    <span>Lesson Library</span>
@endsection

@section('content')

    @php
        // Optional class context: /teacher/learning-space/templates?class=123
        $classId = request()->query('class');
        $classContext = null;

        if ($classId) {
            $classContext = \ToKLearningSpace\Models\LsClass::find($classId);
        }

        // Values from controller (fallback to request() if not set)
        $search        = $search        ?? request()->query('q', '');
        $publishedOnly = $publishedOnly ?? request()->boolean('published_only');
    @endphp

    <div class="tok-header-container">
        <h1 class="tok-ls-page-title">Lesson Library</h1>

        <a href="{{ route('tok-ls.teacher.templates.create') }}"
           class="tok-btn-primary">
            + Create Template
        </a>
    </div>

    <section class="tok-ls-section">

        @if($classContext)
            <p class="tok-ls-subtitle">
                You are choosing a lesson template for class
                <strong>{{ $classContext->name }}</strong>.
                Click <strong>“Use in {{ $classContext->name }}”</strong> to create a new lesson in that class.
            </p>
        @else
            <p class="tok-ls-subtitle">
                Browse your reusable ToK lesson templates. To create a lesson from a template,
                open the Lesson Library from a specific class so the system knows where to put it.
            </p>
        @endif

        {{-- 🔍 Search / filter bar --}}
        <form method="GET"
              action="{{ route('tok-ls.teacher.templates.index') }}"
              class="tok-ls-form"
              style="margin-bottom: 16px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">

            {{-- Preserve class context if present --}}
            @if($classContext)
                <input type="hidden" name="class" value="{{ $classContext->id }}">
            @elseif($classId)
                <input type="hidden" name="class" value="{{ $classId }}">
            @endif

            <div style="flex:1 1 220px;">
                <input
                    type="text"
                    name="q"
                    class="tok-ls-input"
                    placeholder="Search by title, topic, or content…"
                    value="{{ $search }}"
                >
            </div>

            <label style="display:flex; align-items:center; gap:4px; font-size:0.9rem;">
                <input
                    type="checkbox"
                    name="published_only"
                    value="1"
                    {{ $publishedOnly ? 'checked' : '' }}
                >
                Show only published
            </label>

            <button type="submit" class="tok-ls-btn tok-ls-btn--tiny">
                Filter
            </button>

            @if($search || $publishedOnly)
                <a href="{{ route('tok-ls.teacher.templates.index', $classId ? ['class' => $classId] : []) }}"
                   class="tok-ls-link-action"
                   style="font-size:0.85rem;">
                    Reset
                </a>
            @endif
        </form>

        @if ($templates->isEmpty())
            <p class="tok-ls-muted">No templates in the library match your filters.</p>
        @else

            <div class="tok-ls-lesson-grid">
                @foreach ($templates as $template)
                    <article class="tok-ls-lesson-card">
                        <h2 class="tok-ls-lesson-title">
                            {{ $template->title }}
                        </h2>

                        @if (!empty($template->topic))
                            <p class="tok-ls-lesson-meta">
                                <strong>Topic:</strong> {{ $template->topic }}
                            </p>
                        @endif

                        @if (!is_null($template->duration_minutes))
                            <p class="tok-ls-lesson-meta">
                                <strong>Suggested duration:</strong>
                                {{ $template->duration_minutes }} min
                            </p>
                        @endif

                        <p class="tok-ls-lesson-meta">
                            <strong>{{ $template->is_published ? 'Published' : 'Draft' }}</strong>
    ·                       Last updated:
                            {{ $template->updated_at?->format('Y-m-d') ?? '—' }}
                        </p>

                        <div style="margin-top:auto; display:flex; gap:8px; flex-wrap:wrap;">
                            @if ($classContext)
                                {{-- Main action: create lesson in this class using this template --}}
                                <a href="{{ route('tok-ls.teacher.lessons.create', [$classContext->id, 'template' => $template->id]) }}"
                                   class="tok-btn-primary">
                                    Use in {{ $classContext->name }}
                                </a>
                            @endif

                            {{-- Edit template --}}
                            <a href="{{ route('tok-ls.teacher.templates.edit', $template->id) }}"
                               class="tok-btn-secondary">
                                Edit Template
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div style="margin-top:16px;">
                {{ $templates->links() }}
            </div>

        @endif

    </section>

@endsection