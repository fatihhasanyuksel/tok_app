<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

use App\Models\User;
use App\Models\Submission;
use App\Models\Version;
use App\Models\Comment;
use App\Models\CommentMessage;
use App\Models\CommentEvent;
use App\Models\GeneralComment;

class ThreadController extends Controller
{
    /**
     * Helper: can the viewer access this thread?
     * Teachers/Admins => always
     * Students => only if they own the submission behind the thread
     */
    private function canViewThread(?User $viewer, Comment $thread): bool
    {
        if (!$viewer) return false;

        $role = strtolower((string) $viewer->role);
        if (in_array($role, ['teacher', 'admin'], true)) {
            return true;
        }

        $submissionStudentId = optional(optional($thread->version)->submission)->student_id;

        return $submissionStudentId !== null && (int) $submissionStudentId === (int) $viewer->id;
    }

    /**
     * Create a new feedback thread (POST /workspace/{type}/thread).
     * We only persist is_resolved (bool). "Awaiting" is derived from last message author.
     */
    public function create(Request $request, string $type)
    {
        abort_unless(in_array($type, ['exhibition', 'essay'], true), 404);

        $viewer = $request->user();
        abort_unless($viewer, 401);

        // Resolve the student this thread belongs to.
        if (strtolower((string) $viewer->role) === 'student') {
            $student = $viewer;
        } else {
            $studentId = (int) $request->query('student', 0);
            abort_if($studentId <= 0, 404, 'Missing student id.');
            $student = User::where('id', $studentId)->where('role', 'student')->first();
            abort_unless($student, 404, 'Student not found.');
        }

        $data = $request->validate([
            'selection_text' => ['nullable', 'string', 'max:255'],
            'body'           => ['required', 'string', 'min:1', 'max:4000'],
            'start_offset'   => ['nullable', 'integer', 'min:0'],
            'end_offset'     => ['nullable', 'integer', 'gte:start_offset'],
        ]);

        // Ensure submission + latest version exist
        $submission = Submission::firstOrCreate(
            ['student_id' => $student->id, 'type' => $type],
            ['status' => 'draft']
        );

        $version = $submission->latestVersion()->first()
            ?: Version::create([
                'submission_id' => $submission->id,
                'body_html'     => '<p><em>Start writing…</em></p>',
                'files_json'    => [],
            ]);

        // Create thread
        $thread = Comment::create([
            'version_id'     => $version->id,
            'author_id'      => $viewer->id,
            'selection_text' => $data['selection_text'] ?? null,
            'start_offset'   => $data['start_offset'] ?? null,
            'end_offset'     => $data['end_offset'] ?? null,
        ]);

        // First message
        CommentMessage::create([
            'comment_id' => $thread->id,
            'author_id'  => $viewer->id,
            'body'       => trim($data['body']),
        ]);

        // Audit
        CommentEvent::create([
            'comment_id'   => $thread->id,
            'triggered_by' => $viewer->id,
            'event'        => 'created',
        ]);

        // AJAX-friendly response
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok'  => true,
                'id'  => $thread->id,
                'url' => route('thread.show', [
                    'type'    => $type,
                    'thread'  => $thread->id,
                    'student' => (strtolower((string) $viewer->role) === 'student') ? null : $student->id,
                ]),
            ], 201);
        }

        // Non-AJAX fallback
        return redirect()->route('thread.show', [
            'type'    => $type,
            'thread'  => $thread->id,
            'student' => (strtolower((string) $viewer->role) === 'student') ? null : $student->id,
        ])->with('ok', 'New feedback thread created.');
    }

    /**
     * Show a single thread inside the workspace (returns full page or partial for side-pane).
     */
    public function show(Request $request, string $type, int $thread)
    {
        abort_unless(in_array($type, ['exhibition', 'essay']), 404);

        $viewer    = Auth::user();
        $studentId = $request->query('student');

        if (!$viewer && !$studentId) {
            return redirect('/login');
        }

        // Load thread + relationships. IMPORTANT: include author.role everywhere.
        $activeThread = Comment::with([
            'author:id,role,name',
            'anchor',
            'version.submission',
            'messages' => fn ($q) => $q->with('author:id,role,name')->orderBy('created_at', 'asc'),
            'events'   => fn ($q) => $q->with('triggeredBy:id,role,name')->orderBy('created_at', 'asc'),
        ])->findOrFail($thread);

        // Authorization
        abort_unless($this->canViewThread($viewer, $activeThread), 403, 'Access denied.');

        $latestVersion = $activeThread->version;
        $submission    = $latestVersion->submission;

        // Display student in header based on actual submission owner
        $studentDisplay = $submission && $submission->student_id
            ? User::find($submission->student_id)
            : ($studentId ? User::find((int) $studentId) : $viewer);

        // Latest general comment for this version (optional)
        $general = GeneralComment::where('version_id', $latestVersion->id)
            ->with('author:id,name')
            ->latest()
            ->first();

        // All threads for this submission (newest first) → list in the right pane
        $threads = Comment::whereHas('version', function ($q) use ($submission) {
                $q->where('submission_id', $submission->id);
            })
            ->with('author:id,role,name')
            ->orderBy('created_at', 'desc')
            ->get();

        // Partial return (for side panel)
        if ($request->boolean('partial') || $request->ajax()) {
            return response()->view('partials.thread', [
                'type'          => $type,
                'student'       => $studentDisplay,
                'submission'    => $submission,
                'latestVersion' => $latestVersion,
                'thread'        => $activeThread,
                'threads'       => $threads,
            ]);
        }

        // Full workspace page
        return view('workspace', [
            'type'          => $type,
            'student'       => $studentDisplay,
            'submission'    => $submission,
            'latestVersion' => $latestVersion,
            'thread'        => $activeThread,   // opens side pane in thread mode
            'threads'       => $threads,        // list on the right
            'general'       => $general,
        ]);
    }

    /**
     * Post a reply (auto-unresolve any resolved thread).
     */
    public function reply(Request $request, string $type, int $thread)
    {
        abort_unless(in_array($type, ['exhibition', 'essay']), 404);

        $viewer    = Auth::user();
        $studentId = $request->query('student');

        if (!$viewer && !$studentId) {
            return redirect('/login');
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $comment = Comment::with('version.submission')->findOrFail($thread);

        abort_unless($this->canViewThread($viewer, $comment), 403, 'Access denied.');

        $body = trim($data['body']);
        if ($body === '') {
            return back()->withErrors(['body' => 'Reply cannot be empty.']);
        }

        CommentMessage::create([
            'comment_id' => $comment->id,
            'author_id'  => $viewer->id,
            'body'       => $body,
        ]);

        CommentEvent::create([
            'comment_id'   => $comment->id,
            'triggered_by' => $viewer->id,
            'event'        => 'replied',
        ]);

        // Clear resolve on any reply
        if ($comment->is_resolved) {
            $comment->is_resolved = false;

            CommentEvent::create([
                'comment_id'   => $comment->id,
                'triggered_by' => $viewer->id,
                'event'        => 'unresolved:on_reply',
            ]);
        }

        $comment->save();

        return redirect()->route('thread.show', [
            'type'    => $type,
            'thread'  => $comment->id,
            'student' => $studentId,
        ])->with('ok', 'Reply added.');
    }

    /**
     * Live poll: return latest messages HTML + last message id for a thread.
     */
    public function poll(Request $request, string $type, int $thread)
    {
        abort_unless(in_array($type, ['exhibition', 'essay']), 404);

        $viewer    = Auth::user();
        $studentId = $request->query('student');

        if (!$viewer && !$studentId) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        // Need ownership check + authors with role for correct bubble alignment etc.
        $threadModel = Comment::with([
            'messages.author:id,role,name',
            'version.submission'
        ])->findOrFail($thread);

        if (!$this->canViewThread($viewer, $threadModel)) {
            return response()->json(['ok' => false, 'error' => 'forbidden'], 403);
        }

        $student = $threadModel->version && $threadModel->version->submission
            ? User::find($threadModel->version->submission->student_id)
            : ($studentId ? User::find((int) $studentId) : $viewer);

        $messages = $threadModel->messages;
        $lastId   = $messages->count() ? (int) $messages->last()->id : 0;

        $html = view('partials.thread_messages', [
            'thread'  => $threadModel,
            'student' => $student,
        ])->render();

        return response()->json([
            'ok'      => true,
            'html'    => $html,
            'last_id' => $lastId,
        ]);
    }

    /**
     * Deprecated: manual status workflow.
     */
    public function setStatus(Request $request, string $type, int $thread)
    {
        abort_unless(in_array($type, ['exhibition', 'essay']), 404);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok'    => false,
                'error' => 'Status workflow removed. Use Resolve (teacher) or just reply (auto-unresolve).',
            ], 410);
        }

        return redirect()->back()->with(
            'ok',
            'Statuses are deprecated. Use the green “Resolve” button (teacher) or reply to continue the thread.'
        );
    }

    /**
     * Mark "current user is typing" for ~6 seconds.
     */
    public function typing(Request $request, string $type, int $thread)
    {
        abort_unless(in_array($type, ['exhibition', 'essay']), 404);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['ok' => false], 401);
        }

        // Ensure the user can view the thread
        $threadModel = Comment::with('version.submission')->findOrFail($thread);
        if (!$this->canViewThread($user, $threadModel)) {
            return response()->json(['ok' => false], 403);
        }

        $key = "typing:t{$thread}:u{$user->id}";
        Cache::put($key, now()->timestamp, now()->addSeconds(6));

        return response()->json(['ok' => true]);
    }

    /**
     * Return names of OTHER participants currently typing (within last ~6s).
     */
    public function typingStatus(Request $request, string $type, int $thread)
    {
        abort_unless(in_array($type, ['exhibition', 'essay']), 404);

        $viewer = Auth::user();
        if (!$viewer) {
            return response()->json(['ok' => false], 401);
        }

        // Ensure the user can view the thread
        $comment = Comment::with(['messages.author:id,role,name', 'version.submission'])->findOrFail($thread);
        if (!$this->canViewThread($viewer, $comment)) {
            return response()->json(['ok' => false], 403);
        }

        $participants = collect($comment->messages)->pluck('author')->filter()->unique('id');
        $nowTs = now()->timestamp;

        $typing = [];
        foreach ($participants as $p) {
            if (!$p) continue;
            if ($p->id === $viewer->id) continue;
            $key = "typing:t{$thread}:u{$p->id}";
            $ts  = Cache::get($key);
            if ($ts && ($nowTs - (int) $ts) <= 6) {
                $typing[] = $p->name ?? 'Someone';
            }
        }

        return response()->json([
            'ok'        => true,
            'typing_by' => array_values(array_unique($typing)),
        ]);
    }

    /**
     * Mark a feedback thread as resolved (teacher/admin only).
     */
    public function resolve(Request $request, string $type, int $thread)
    {
        abort_unless(in_array($type, ['exhibition', 'essay']), 404);

        $viewer = Auth::user();
        abort_unless($viewer, 401);

        $role = strtolower((string) $viewer->role);
        abort_unless(in_array($role, ['teacher', 'admin'], true), 403, 'Only teachers/admins can resolve.');

        $comment = Comment::with('version.submission')->findOrFail($thread);
        abort_unless($this->canViewThread($viewer, $comment), 403, 'Access denied.');

        if (!$comment->is_resolved) {
            $comment->is_resolved = true;
            $comment->save();

            CommentEvent::create([
                'comment_id'   => $comment->id,
                'triggered_by' => $viewer->id,
                'event'        => 'resolved',
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'is_resolved' => (bool) $comment->is_resolved]);
        }

        return redirect()->back()->with('ok', 'Thread marked as resolved.');
    }
}