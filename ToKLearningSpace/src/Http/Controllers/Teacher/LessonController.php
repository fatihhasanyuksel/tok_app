<?php

namespace ToKLearningSpace\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ToKLearningSpace\Models\LsClass;
use ToKLearningSpace\Models\LsLesson;
use ToKLearningSpace\Models\LsResponse;
use ToKLearningSpace\Models\LessonImage;
use ToKLearningSpace\Models\LsTemplate; // ⭐ NEW

class LessonController extends Controller
{
    // -------------------------------------------------
    // 1) RETIRED: Show all lessons page
    //    Now redirect to class page with lesson cards
    // -------------------------------------------------
    public function index(LsClass $class)
    {
        return redirect()->route('tok-ls.teacher.classes.show', $class->id);
    }

    // -------------------------------------------------
    // 2) Show Create Lesson form
    // -------------------------------------------------
    public function createForm(LsClass $class, Request $request)
    {
        $template = null;

        // If ?template=ID exists (coming from "Use in 11B" etc.)
        if ($request->filled('template')) {
            $templateId = (int) $request->query('template');

            $template = LsTemplate::find($templateId);
        }

        return view('tok_ls::teacher.lessons.create', [
            'class'    => $class,
            'template' => $template,
        ]);
    }

    // -------------------------------------------------
    // 3) Store new lesson
    // -------------------------------------------------
    public function store(LsClass $class, Request $request)
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'objectives'       => 'nullable|string',
            'success_criteria' => 'nullable|string',
            'content'          => 'nullable|string',
            'status'           => 'required|in:draft,published',
            // тнР NEW
            'duration_minutes' => 'nullable|integer|min:0',
        ]);

        $lesson = LsLesson::create([
            'class_id'         => $class->id,
            'teacher_id'       => auth()->id(),
            'title'            => $validated['title'],
            'objectives'       => $validated['objectives'] ?? null,
            'success_criteria' => $validated['success_criteria'] ?? null,
            'content'          => $validated['content'] ?? null,
            'status'           => $validated['status'],
            'published_at'     => $validated['status'] === 'published' ? now() : null,
            // тнР NEW
            'duration_minutes' => $validated['duration_minutes'] ?? null,
        ]);

        // ЁЯФз Sync lesson_images + clean up any unused files
        $this->syncLessonImages($lesson, $validated['content'] ?? '');

        return redirect()
            ->route('tok-ls.teacher.lessons.show', [$class->id, $lesson->id])
            ->with('success', 'Lesson created successfully.');
    }

    // -------------------------------------------------
    // 4) Show lesson details + student responses overview
    // -------------------------------------------------
    public function show(LsClass $class, $lessonId, Request $request)
    {
        // Ensure lesson belongs to this class
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        // All students in this class
        $students = $class->students()
            ->orderBy('name')
            ->get();

        // All responses for this lesson, keyed by student_id
        $responsesByStudent = LsResponse::where('lesson_id', $lesson->id)
            ->with('student')
            ->get()
            ->keyBy('student_id');

        // Selected student (via ?student=ID)
        $selectedStudentId = $request->query('student');
        $selectedStudent   = $selectedStudentId
            ? $students->firstWhere('id', (int) $selectedStudentId)
            : null;

        $selectedResponse  = $selectedStudent
            ? $responsesByStudent->get($selectedStudent->id)
            : null;

        // Also provide flat responses for table
        $responses = $responsesByStudent->values();

        return view('tok_ls::teacher.lessons.show', [
            'class'              => $class,
            'lesson'             => $lesson,
            'students'           => $students,
            'responsesByStudent' => $responsesByStudent,
            'selectedStudent'    => $selectedStudent,
            'selectedResponse'   => $selectedResponse,
            'responses'          => $responses,
        ]);
    }

    // -------------------------------------------------
    // 5) Edit Lesson (GET)
    // -------------------------------------------------
    public function editForm(LsClass $class, $lessonId)
    {
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        return view('tok_ls::teacher.lessons.edit', [
            'class'  => $class,
            'lesson' => $lesson,
        ]);
    }

    // -------------------------------------------------
    // 6) Update lesson
    // -------------------------------------------------
    public function update(LsClass $class, $lessonId, Request $request)
    {
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'objectives'       => 'nullable|string',
            'success_criteria' => 'nullable|string',
            'content'          => 'nullable|string',
            'status'           => 'required|in:draft,published',
            // тнР NEW
            'duration_minutes' => 'nullable|integer|min:0',
        ]);

        $lesson->title            = $validated['title'];
        $lesson->objectives       = $validated['objectives'] ?? null;
        $lesson->success_criteria = $validated['success_criteria'] ?? null;
        $lesson->content          = $validated['content'] ?? null;
        $lesson->status           = $validated['status'];
        // тнР NEW
        $lesson->duration_minutes = $validated['duration_minutes'] ?? null;

        // published_at logic
        if ($validated['status'] === 'published' && ! $lesson->published_at) {
            $lesson->published_at = now();
        }
        if ($validated['status'] === 'draft') {
            $lesson->published_at = null;
        }

        $lesson->save();

        // ЁЯФз Sync lesson_images + clean up unused files after update
        $this->syncLessonImages($lesson, $validated['content'] ?? '');

        return redirect()
            ->route('tok-ls.teacher.lessons.show', [$class->id, $lesson->id])
            ->with('success', 'Lesson updated successfully.');
    }

    // -------------------------------------------------
    // 7) Delete lesson
    // -------------------------------------------------
    public function delete(LsClass $class, $lessonId)
    {
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        // ЁЯФ┤ Hard-delete all associated images (files + DB rows)
        $images = LessonImage::where('lesson_id', $lesson->id)->get();

        foreach ($images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        LessonImage::where('lesson_id', $lesson->id)->delete();

        $lesson->delete();

        // Redirect back to class page (new model)
        return redirect()
            ->route('tok-ls.teacher.classes.show', $class->id)
            ->with('success', 'Lesson deleted.');
    }

    // -------------------------------------------------
    // 8) View response + feedback form
    // -------------------------------------------------
    public function showFeedback(LsClass $class, $lessonId, $responseId)
    {
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        $response = LsResponse::where('id', $responseId)
            ->where('lesson_id', $lesson->id)
            ->with('student')
            ->firstOrFail();

        return view('tok_ls::teacher.lessons.feedback', [
            'class'    => $class,
            'lesson'   => $lesson,
            'response' => $response,
            'student'  => $response->student,
        ]);
    }

    // -------------------------------------------------
    // 9) Save feedback
    // -------------------------------------------------
    public function saveFeedback(LsClass $class, $lessonId, $responseId, Request $request)
    {
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        $validated = $request->validate([
            'teacher_feedback' => 'nullable|string',
        ]);

        $response = LsResponse::where('id', $responseId)
            ->where('lesson_id', $lesson->id)
            ->firstOrFail();

        $response->teacher_feedback = $validated['teacher_feedback'] ?? null;
        $response->save();

        return redirect()
            ->route('tok-ls.teacher.lessons.show', [$class->id, $lesson->id, 'student' => $response->student_id])
            ->with('success', 'Feedback saved successfully.');
    }

    // -------------------------------------------------
    // 10) Toggle publish / unpublish
    // -------------------------------------------------
    public function togglePublish(LsClass $class, $lessonId)
    {
        $lesson = LsLesson::where('id', $lessonId)
            ->where('class_id', $class->id)
            ->firstOrFail();

        if ($lesson->published_at) {
            $lesson->published_at = null;
            $lesson->status       = 'draft';
            $message = 'Lesson unpublished. It is now hidden from students.';
        } else {
            $lesson->published_at = now();
            $lesson->status       = 'published';
            $message = 'Lesson published. It is now visible to students.';
        }

        $lesson->save();

        return redirect()
            ->route('tok-ls.teacher.lessons.show', [$class->id, $lesson->id])
            ->with('success', $message);
    }

    // -------------------------------------------------
    // ЁЯФз Helper: sync tok_ls_lesson_images with current HTML
    // -------------------------------------------------
    /**
     * Extract current image paths from HTML, insert missing rows,
     * and hard-delete any images that are no longer referenced.
     */
    protected function syncLessonImages(LsLesson $lesson, ?string $htmlContent = null): void
    {
        $html = $htmlContent ?? (string) $lesson->content ?? '';

        // 1) Extract all <img src="..."> URLs
        $currentPaths = [];

        if (! empty($html)) {
            if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
                $urls = array_unique($matches[1]);

                foreach ($urls as $url) {
                    // Get just the path portion
                    $pathPart = parse_url($url, PHP_URL_PATH); // e.g. /storage/tok-ls/23/lesson-images/abc.webp
                    if (! $pathPart) {
                        continue;
                    }

                    $pathPart = ltrim($pathPart, '/'); // storage/tok-ls/...

                    // We only care about our own public storage images
                    if (str_starts_with($pathPart, 'storage/')) {
                        // Strip leading "storage/" тЖТ DB path looks like "tok-ls/23/lesson-images/abc.webp"
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
        $existing = LessonImage::where('lesson_id', $lesson->id)->get();
        $existingPaths = $existing->pluck('path')->all();

        // 3) Insert new ones
        $toAdd = array_diff($currentPaths, $existingPaths);
        foreach ($toAdd as $path) {
            LessonImage::create([
                'lesson_id' => $lesson->id,
                'path'      => $path,
            ]);
        }

        // 4) Remove unused ones (DB + file)
        $toRemove = array_diff($existingPaths, $currentPaths);
        if (! empty($toRemove)) {
            foreach ($toRemove as $path) {
                Storage::disk('public')->delete($path);

                LessonImage::where('lesson_id', $lesson->id)
                    ->where('path', $path)
                    ->delete();
            }
        }
    }
}