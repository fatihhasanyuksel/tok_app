<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

// For targeted CSRF bypass on TipTap AJAX
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as FrameworkVerifyCsrf;

use App\Models\Teacher;

use App\Http\Controllers\AuthController;  // Unified auth
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\TeacherController as AdminTeacherController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController; // Admin Students
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\ResourcesController;
use App\Http\Controllers\GeneralMessageController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentStartController;
// TipTap rich text API + Upload controller
use App\Http\Controllers\Tok\RichTextController;
use App\Http\Controllers\Tok\UploadController;

// Class-based role middleware (no alias)
use App\Http\Middleware\EnsureRole;

/*
|--------------------------------------------------------------------------
| Public / Shared Routes
|--------------------------------------------------------------------------
*/
Route::middleware('web')->group(function () {

    // Smart root: redirect by role, fallback to unified login
    Route::get('/', function (Request $r) {
        $u = Auth::user();

        if ($u) {
            if ($u->role === 'student') {
                return redirect()->route('student.dashboard');
            }
            if ($u->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            // teacher (default)
            return redirect()->route('students.index');
        }

        return redirect()->route('login');
    })->name('home');

    // Unified auth
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Legacy redirect
    Route::permanentRedirect('/teacher/login', '/login');

    // Public resources (read-only)
    Route::get('/resources', [ResourcesController::class, 'index'])->name('resources.index');

    // Student dashboard
    Route::get('/student', [StudentDashboardController::class, 'index'])->name('student.dashboard');

    // Student start/resume submission (Exhibition or Essay)
    Route::post('/student/start/{type}', [StudentStartController::class, 'start'])
        ->whereIn('type', ['exhibition', 'essay'])
        ->name('student.start');

    // Shared workspace (view + draft save)
    Route::get('/workspace/{type}', [FeedbackController::class, 'workspace'])
        ->whereIn('type', ['exhibition', 'essay'])
        ->name('workspace.show');

    Route::post('/workspace/{type}/save', [FeedbackController::class, 'saveDraft'])
        ->whereIn('type', ['exhibition', 'essay'])
        ->name('workspace.save');

    Route::get('/workspace/{type}/history', [FeedbackController::class, 'history'])
        ->whereIn('type', ['exhibition', 'essay'])
        ->name('workspace.history');

    // Export route
    Route::get('/workspace/{type}/export', [FeedbackController::class, 'export'])
        ->whereIn('type', ['exhibition', 'essay'])
        ->name('workspace.export');

    Route::post('/workspace/{type}/restore/{version}', [FeedbackController::class, 'restore'])
        ->whereIn('type', ['exhibition', 'essay'])
        ->whereNumber('version')
        ->name('workspace.restore');
});

/*
|--------------------------------------------------------------------------
| General Messages (all authenticated roles)
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/workspace/{type}/general/{submission}', [GeneralMessageController::class, 'index'])
        ->whereIn('type', ['exhibition', 'essay'])
        ->name('general.index');

    Route::get('/workspace/{type}/general/{submission}/unread', [GeneralMessageController::class, 'unread'])
        ->whereIn('type', ['exhibition', 'essay'])
        ->name('general.unread');

    Route::post('/workspace/{type}/general/{submission}', [GeneralMessageController::class, 'store'])
        ->whereIn('type', ['exhibition', 'essay'])
        ->name('general.store');
});

/*
|--------------------------------------------------------------------------
| Threads (any authenticated user)
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/workspace/{type}/thread/{thread}', [ThreadController::class, 'show'])
        ->whereIn('type', ['exhibition', 'essay'])
        ->whereNumber('thread')
        ->name('thread.show');

    Route::get('/workspace/{type}/thread/{thread}/poll', [ThreadController::class, 'poll'])
        ->middleware('throttle:60,1')
        ->whereIn('type', ['exhibition', 'essay'])
        ->whereNumber('thread')
        ->name('thread.poll');

    Route::post('/workspace/{type}/thread/{thread}/reply', [ThreadController::class, 'reply'])
        ->whereIn('type', ['exhibition', 'essay'])
        ->whereNumber('thread')
        ->name('thread.reply');

    Route::post('/workspace/{type}/thread/{thread}/typing', [ThreadController::class, 'typing'])
        ->middleware('throttle:120,1')
        ->whereIn('type', ['exhibition', 'essay'])
        ->whereNumber('thread')
        ->name('thread.typing');

    Route::get('/workspace/{type}/thread/{thread}/typing', [ThreadController::class, 'typingStatus'])
        ->middleware('throttle:120,1')
        ->whereIn('type', ['exhibition', 'essay'])
        ->whereNumber('thread')
        ->name('thread.typingStatus');
});

/*
|--------------------------------------------------------------------------
| Admin-only area (role:admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['web', EnsureRole::class . ':admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    Route::get('/admin/transfer',  [AdminController::class, 'transferForm'])->name('admin.transfer');
    Route::post('/admin/transfer', [AdminController::class, 'transferDo'])->name('admin.transfer.do');

    Route::resource('admin/teachers', AdminTeacherController::class)
        ->except(['show'])
        ->names('admin.teachers');

    Route::post('admin/teachers/{teacher}/reset-password', [AdminTeacherController::class, 'resetPassword'])
        ->name('admin.teachers.reset');

    Route::resource('admin/students', AdminStudentController::class)
        ->except(['show'])
        ->names('admin.students');

    Route::post('admin/students/{student}/reset-password', [AdminStudentController::class, 'resetPassword'])
        ->name('admin.students.reset');
});

/*
|--------------------------------------------------------------------------
| Teacher/Admin shared area (role:teacher OR role:admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['web', EnsureRole::class . ':teacher,admin'])->group(function () {
    Route::post('/workspace/{type}/thread', [ThreadController::class, 'create'])
        ->whereIn('type', ['exhibition', 'essay'])
        ->name('thread.create');

    Route::post('/workspace/{type}/thread/{thread}/status', [ThreadController::class, 'setStatus'])
        ->whereIn('type', ['exhibition', 'essay'])
        ->whereNumber('thread')
        ->name('thread.status');

    Route::resource('students', StudentController::class);

    Route::get('/resources/manage', [ResourcesController::class, 'manage'])->name('resources.manage');
    Route::post('/resources/upload', [ResourcesController::class, 'upload'])->name('resources.upload');
    Route::delete('/resources/{filename}', [ResourcesController::class, 'destroy'])
        ->where('filename', '.*')
        ->name('resources.destroy');

    // Dev aids (auth context preserved)
    if (app()->environment('local') || env('ALLOW_DEV_ROUTES', false)) {
        Route::get('/dev/session-ping', function (Request $r) {
            if (!$r->session()->has('ping')) {
                $r->session()->put('ping', 'pong-' . uniqid());
                $r->session()->save();
                return response()->json(['wrote' => true, 'value' => $r->session()->get('ping')]);
            }
            return response()->json(['wrote' => false, 'value' => $r->session()->get('ping')]);
        });

        Route::get('/dev/whoami', function () {
            $user = Auth::user();
            $teacher = $user ? \App\Models\Teacher::where('email', $user->email)->first() : null;

            return response()->json([
                'auth_user'          => $user ? $user->only(['id','name','email','role']) : null,
                'legacy_teacher_row' => $teacher ? $teacher->only(['id','name','email','active','is_admin']) : null,
            ]);
        });

        Route::get('/dev/activate-teacher', function (Request $r) {
            $email = $r->query('email', 'hasan.yuksel@australianschool.ae');
            $t = Teacher::where('email', $email)->first();
            if (!$t) return 'No teacher found for ' . $email;
            $t->active = 1;
            if (!$t->password) {
                $t->password = \Illuminate\Support\Facades\Hash::make('Password123!');
            }
            $t->save();
            return 'Teacher activated: ' . $email . ' (Password reset if it was empty).';
        });
    }
});

/*
|--------------------------------------------------------------------------
| ToK Rich Text Editor API (session-auth; TipTap integration)
|--------------------------------------------------------------------------
| Lives under /api/tok, uses session auth. Patch/Post bypass CSRF only.
*/
Route::middleware(['web', 'auth'])->prefix('api/tok')->group(function () {
    Route::get('/docs/{owner_type}/{owner_id}', [RichTextController::class, 'show'])
        ->where(['owner_type' => '[A-Za-z_-]+', 'owner_id' => '[0-9]+']);

    Route::patch('/docs/{owner_type}/{owner_id}', [RichTextController::class, 'autosave'])
        ->withoutMiddleware([FrameworkVerifyCsrf::class])
        ->where(['owner_type' => '[A-Za-z_-]+', 'owner_id' => '[0-9]+']);

    Route::post('/docs/{owner_type}/{owner_id}/commit', [RichTextController::class, 'commit'])
        ->withoutMiddleware([FrameworkVerifyCsrf::class])
        ->where(['owner_type' => '[A-Za-z_-]+', 'owner_id' => '[0-9]+']);

    Route::post('/uploads/images', [UploadController::class, 'store'])
        ->name('tok.uploads.images'); // keep CSRF for uploads
});

/*
|--------------------------------------------------------------------------
| TEMP TipTap test page
|--------------------------------------------------------------------------
*/
Route::get('/tiptap-test', function () {
    return view('tiptap-test');
})->name('tiptap.test');

/*
|--------------------------------------------------------------------------
| DEV-ONLY: zero-auth smoke tests for cURL (no auth / no CSRF)
|--------------------------------------------------------------------------
*/
if (app()->environment('local') || env('ALLOW_DEV_ROUTES', false)) {
    Route::prefix('api/tok/test')
        ->withoutMiddleware([FrameworkVerifyCsrf::class]) // no CSRF
        ->group(function () {
            Route::get('/docs/{owner_type}/{owner_id}', [RichTextController::class, 'show'])
                ->where(['owner_type' => '[A-Za-z_-]+', 'owner_id' => '[0-9]+']);

            Route::patch('/docs/{owner_type}/{owner_id}', [RichTextController::class, 'autosave'])
                ->where(['owner_type' => '[A-Za-z_-]+', 'owner_id' => '[0-9]+']);

            Route::post('/docs/{owner_type}/{owner_id}/commit', [RichTextController::class, 'commit'])
                ->where(['owner_type' => '[A-Za-z_-]+', 'owner_id' => '[0-9]+']);
        });
}