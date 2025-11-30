<?php

namespace ToKLearningSpace\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ToKLearningSpace\Models\LsTemplate;
use ToKLearningSpace\Models\LsClass;      // from-lesson helper
use ToKLearningSpace\Models\LsLesson;     // from-lesson helper
use ToKLearningSpace\Models\TemplateImage;

class TemplateController extends Controller
{
    /**
     * List templates.
     *
     * Shows published templates by default.
     * Add ?show_drafts=1 to see drafts as well.
     */
    public function index(Request $request)
    {
        $showDrafts = $request->boolean('show_drafts', false);
        $search     = trim($request->query('q', ''));

        $query = LsTemplate::query()->orderByDesc('updated_at');

        if (! $showDrafts) {
            $query->where('is_published', true);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('topic', 'like', "%{$search}%")
                  ->orWhere('content_text', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return view('tok_ls::teacher.templates.index', [
            'templates'  => $query->paginate(20),
            'user'       => Auth::user(),
            'showDrafts' => $showDrafts,
            'search'     => $search,
        ]);
    }

    /**
     * Create form.
     */
    public function create()
    {
        return view('tok_ls::teacher.templates.create', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Store new template.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'topic'            => ['nullable', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'objectives'       => ['nullable', 'string'],
            'success_criteria' => ['nullable', 'string'],
            'content_html'     => ['nullable', 'string'],
            'content_text'     => ['nullable', 'string'],
            'notes'            => ['nullable', 'string'],
            'is_published'     => ['nullable', 'boolean'],
        ]);

        // keep content_text in sync
        $data['content_text'] = strip_tags($data['content_html'] ?? '');

        $data['is_published'] = $request->boolean('is_published');
        $data['created_by']   = $user->id;
        $data['updated_by']   = $user->id;

        $template = LsTemplate::create($data);

        // 🔗 NEW — sync template images from HTML into tok_ls_template_images
        $this->syncTemplateImages($template, $data['content_html'] ?? '');

        return redirect()
            ->route('tok-ls.teacher.templates.edit', $template->id)
            ->with('success', 'Template created in Lesson Library.');
    }

    /**
     * Edit form.
     */
    public function edit(LsTemplate $template)
    {
        return view('tok_ls::teacher.templates.edit', [
            'template' => $template,
            'user'     => Auth::user(),
        ]);
    }

    /**
     * Update template.
     */
    public function update(Request $request, LsTemplate $template)
    {
        $user = Auth::user();

        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'topic'            => ['nullable', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'objectives'       => ['nullable', 'string'],
            'success_criteria' => ['nullable', 'string'],
            'content_html'     => ['nullable', 'string'],
            'content_text'     => ['nullable', 'string'],
            'notes'            => ['nullable', 'string'],
            'is_published'     => ['nullable', 'boolean'],
        ]);

        // keep content_text in sync
        $data['content_text'] = strip_tags($data['content_html'] ?? '');

        $data['is_published'] = $request->boolean('is_published');
        $data['updated_by']   = $user->id;

        $template->update($data);

        // 🔗 NEW — sync images after update
        $this->syncTemplateImages($template, $data['content_html'] ?? '');

        return redirect()
            ->route('tok-ls.teacher.templates.edit', $template->id)
            ->with('success', 'Template updated.');
    }

    /**
     * Hard delete.
     */
    public function destroy(LsTemplate $template)
    {
        // Clean up any tracked template images (DB + files)
        $images = TemplateImage::where('template_id', $template->id)->get();

        foreach ($images as $image) {
            if ($image->path) {
                Storage::disk('public')->delete($image->path);
            }
        }

        TemplateImage::where('template_id', $template->id)->delete();

        $template->delete();

        return redirect()
            ->route('tok-ls.teacher.templates.index')
            ->with('success', 'Template deleted from Lesson Library.');
    }

    /**
     * Create a template from an existing lesson.
     *
     * POST /teacher/learning-space/classes/{class}/lessons/{lesson}/save-as-template
     */
    public function storeFromLesson(LsClass $class, LsLesson $lesson)
    {
        $user = Auth::user();

        // Safety: ensure the lesson belongs to this class
        if ((int) $lesson->class_id !== (int) $class->id) {
            abort(404);
        }

        $data = [
            'title'            => $lesson->title,
            'topic'            => null,
            'duration_minutes' => $lesson->duration_minutes,
            'objectives'       => $lesson->objectives,
            'success_criteria' => $lesson->success_criteria,
            'content_html'     => $lesson->content,
            'content_text'     => strip_tags($lesson->content ?? ''),
            'notes'            => 'Imported from lesson "' . $lesson->title
                                   . '" in class "' . $class->name
                                   . '" on ' . now()->format('Y-m-d'),
            'is_published'     => false,
            'created_by'       => $user->id,
            'updated_by'       => $user->id,
        ];

        $template = LsTemplate::create($data);

        // 🔗 NEW — also track any images that came from the source lesson HTML
        $this->syncTemplateImages($template, $lesson->content ?? '');

        return redirect()
            ->route('tok-ls.teacher.templates.edit', $template->id)
            ->with('success', 'Lesson saved as a template in the Lesson Library.');
    }

    // -------------------------------------------------
    // Helper: sync tok_ls_template_images with current HTML
    // -------------------------------------------------
    /**
     * Extract current <img> src paths from HTML, insert missing rows,
     * and delete any images that are no longer referenced.
     */
    protected function syncTemplateImages(LsTemplate $template, ?string $htmlContent = null): void
    {
        $html = $htmlContent ?? (string) $template->content_html ?? '';

        $currentPaths = [];

        if (! empty($html)) {
            if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
                $urls = array_unique($matches[1]);

                foreach ($urls as $url) {
                    $pathPart = parse_url($url, PHP_URL_PATH); // e.g. /storage/tok-ls/23/template-images/abc.webp
                    if (! $pathPart) {
                        continue;
                    }

                    $pathPart = ltrim($pathPart, '/'); // storage/tok-ls/...

                    // Only care about our own public storage images
                    if (str_starts_with($pathPart, 'storage/')) {
                        // Strip leading "storage/" → DB path: "tok-ls/.../template-images/abc.webp"
                        $relative = substr($pathPart, strlen('storage/'));
                        if ($relative) {
                            $currentPaths[] = $relative;
                        }
                    }
                }
            }
        }

        $currentPaths = array_values(array_unique($currentPaths));

        // 2) Existing entries in DB
        $existing      = TemplateImage::where('template_id', $template->id)->get();
        $existingPaths = $existing->pluck('path')->all();

        // 3) Insert new ones
        $toAdd = array_diff($currentPaths, $existingPaths);
        foreach ($toAdd as $path) {
            TemplateImage::create([
                'template_id' => $template->id,
                'path'        => $path,
                'alt'         => null,
            ]);
        }

        // 4) Remove unused ones (DB + file)
        $toRemove = array_diff($existingPaths, $currentPaths);
        if (! empty($toRemove)) {
            foreach ($toRemove as $path) {
                Storage::disk('public')->delete($path);

                TemplateImage::where('template_id', $template->id)
                    ->where('path', $path)
                    ->delete();
            }
        }
    }
}