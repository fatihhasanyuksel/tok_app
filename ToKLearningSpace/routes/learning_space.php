<?php

use Illuminate\Support\Facades\Route;
use ToKLearningSpace\Models\LsClass;
use ToKLearningSpace\Http\Controllers\Student\ClassController as StudentClassController;
use ToKLearningSpace\Http\Controllers\Student\LessonResponseController;
use ToKLearningSpace\Http\Controllers\Teacher\ClassController;
use ToKLearningSpace\Http\Controllers\Teacher\LessonController;
use ToKLearningSpace\Http\Controllers\Teacher\LessonImageUploadController;
use ToKLearningSpace\Http\Controllers\Teacher\TemplateController;
use ToKLearningSpace\Http\Controllers\Teacher\TemplateImageUploadController; // ⭐ NEW

// -------------------------
// Student routes
// -------------------------
Route::middleware(['web', 'auth'])->group(function () {

    // Student home: auto-redirect to their first class (MVP)
    Route::get('/student/learning-space', function () {
        $user = auth()->user();

        $class = LsClass::whereHas('students', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->orderBy('id')
            ->first();

        if (! $class) {
            return "You are not assigned to any ToK Learning Space class yet.";
        }

        return redirect()->route('tok-ls.student.classes.show', $class->id);
    })->name('tok-ls.student.home');

    // Student class — show published lessons
    Route::get(
        '/student/learning-space/classes/{class}',
        [StudentClassController::class, 'show']
    )->name('tok-ls.student.classes.show');

    // Student lesson page (3-box interface)
    Route::get(
        '/student/learning-space/classes/{class}/lessons/{lesson}',
        [LessonResponseController::class, 'showForm']
    )->name('tok-ls.student.lessons.respond');

    // Save student response
    Route::post(
        '/student/learning-space/classes/{class}/lessons/{lesson}',
        [LessonResponseController::class, 'saveResponse']
    )->name('tok-ls.student.lessons.save-response');
});


// -------------------------
// Teacher routes
// -------------------------
Route::middleware(['web', 'auth'])->group(function () {

    // -------------------------
    // Classes
    // -------------------------

    Route::get(
        '/teacher/learning-space',
        [ClassController::class, 'index']
    )->name('tok-ls.teacher.classes');

    Route::get(
        '/teacher/learning-space/classes/create',
        [ClassController::class, 'createForm']
    )->name('tok-ls.teacher.classes.create');

    Route::post(
        '/teacher/learning-space/classes/create',
        [ClassController::class, 'store']
    )->name('tok-ls.teacher.classes.store');

    Route::get(
        '/teacher/learning-space/classes/{class}',
        [ClassController::class, 'show']
    )->name('tok-ls.teacher.classes.show');

    Route::delete(
        '/teacher/learning-space/classes/{class}',
        [ClassController::class, 'destroy']
    )->name('tok-ls.teacher.classes.destroy');

    // ⭐ archive (soft hide) class
    Route::post(
        '/teacher/learning-space/classes/{class}/archive',
        [ClassController::class, 'archive']
    )->name('tok-ls.teacher.classes.archive');

    // ⭐ NEW: unarchive class
    Route::post(
        '/teacher/learning-space/classes/{class}/unarchive',
        [ClassController::class, 'unarchive']
    )->name('tok-ls.teacher.classes.unarchive');

    // ⭐ view archived classes list
    Route::get(
        '/teacher/learning-space/classes-archived',
        [ClassController::class, 'archived']
    )->name('tok-ls.teacher.classes.archived');

    Route::get(
        '/teacher/learning-space/classes/{class}/students/add',
        [ClassController::class, 'addStudents']
    )->name('tok-ls.teacher.classes.students.add');

    Route::post(
        '/teacher/learning-space/classes/{class}/students/add',
        [ClassController::class, 'storeStudents']
    )->name('tok-ls.teacher.classes.students.store');

    Route::post(
        '/teacher/learning-space/classes/{class}/students/{student}/remove',
        [ClassController::class, 'removeStudent']
    )->name('tok-ls.teacher.classes.students.remove');


    // -------------------------
    // Lessons (per class)
    // -------------------------

    Route::get(
        '/teacher/learning-space/classes/{class}/lessons',
        [LessonController::class, 'index']
    )->name('tok-ls.teacher.lessons.index');

    Route::get(
        '/teacher/learning-space/classes/{class}/lessons/create',
        [LessonController::class, 'createForm']
    )->name('tok-ls.teacher.lessons.create');

    Route::post(
        '/teacher/learning-space/classes/{class}/lessons/create',
        [LessonController::class, 'store']
    )->name('tok-ls.teacher.lessons.store');

    Route::get(
        '/teacher/learning-space/classes/{class}/lessons/{lesson}',
        [LessonController::class, 'show']
    )->name('tok-ls.teacher.lessons.show');

    Route::get(
        '/teacher/learning-space/classes/{class}/lessons/{lesson}/edit',
        [LessonController::class, 'editForm']
    )->name('tok-ls.teacher.lessons.edit');

    Route::post(
        '/teacher/learning-space/classes/{class}/lessons/{lesson}/edit',
        [LessonController::class, 'update']
    )->name('tok-ls.teacher.lessons.update');

    Route::delete(
        '/teacher/learning-space/classes/{class}/lessons/{lesson}',
        [LessonController::class, 'delete']
    )->name('tok-ls.teacher.lessons.delete');


    // ----------------------------------------------------
    // Lesson responses / feedback
    // ----------------------------------------------------
    Route::get(
        '/teacher/learning-space/classes/{class}/lessons/{lesson}/responses/{response}',
        [LessonController::class, 'showFeedback']
    )->name('tok-ls.teacher.lessons.responses.show');

    // Save teacher feedback
    Route::post(
        '/teacher/learning-space/classes/{class}/lessons/{lesson}/responses/{response}/feedback',
        [LessonController::class, 'saveFeedback']
    )->name('tok-ls.teacher.lessons.responses.feedback');

    // AUTOSAVE teacher feedback (not implemented yet, but route kept as-is)
    Route::post(
        '/teacher/learning-space/classes/{class}/lessons/{lesson}/responses/{response}/autosave-feedback',
        [LessonController::class, 'autosaveFeedback']
    )->name('tok-ls.teacher.lessons.responses.autosave');

    // Publish/unpublish
    Route::post(
        '/teacher/learning-space/classes/{class}/lessons/{lesson}/toggle-publish',
        [LessonController::class, 'togglePublish']
    )->name('tok-ls.teacher.lessons.toggle-publish');

    // Teacher image upload (class lessons)
    Route::post(
        '/teacher/learning-space/lesson-images/upload',
        [LessonImageUploadController::class, 'store']
    )->name('tok-ls.teacher.lesson-images.upload');

    // ⭐ Teacher image upload (lesson templates in library)
    Route::post(
        '/teacher/learning-space/template-images/upload',
        [TemplateImageUploadController::class, 'store']
    )->name('tok-ls.teacher.template-images.upload');


    // -------------------------
    // Lesson Library (Templates)
    // -------------------------

    Route::get(
        '/teacher/learning-space/templates',
        [TemplateController::class, 'index']
    )->name('tok-ls.teacher.templates.index');

    Route::get(
        '/teacher/learning-space/templates/create',
        [TemplateController::class, 'create']
    )->name('tok-ls.teacher.templates.create');

    Route::post(
        '/teacher/learning-space/templates',
        [TemplateController::class, 'store']
    )->name('tok-ls.teacher.templates.store');

    Route::get(
        '/teacher/learning-space/templates/{template}/edit',
        [TemplateController::class, 'edit']
    )->name('tok-ls.teacher.templates.edit');

    Route::put(
        '/teacher/learning-space/templates/{template}',
        [TemplateController::class, 'update']
    )->name('tok-ls.teacher.templates.update');

    Route::delete(
        '/teacher/learning-space/templates/{template}',
        [TemplateController::class, 'destroy']
    )->name('tok-ls.teacher.templates.destroy');

    // Save an existing lesson as a template
    Route::post(
        '/teacher/learning-space/classes/{class}/lessons/{lesson}/save-as-template',
        [TemplateController::class, 'storeFromLesson']
    )->name('tok-ls.teacher.templates.store-from-lesson');
});