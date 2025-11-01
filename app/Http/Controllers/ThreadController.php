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
     * Create a new feedback thread.
     * POST /workspace/{type}/thread  (route name: thread.create)
     *
     * Auto-status on creation:
     * - Teacher/Admin author  => status 'open'    (Awaiting Student)
     * - Student author        => status 'revised' (Awaiting Teacher)
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

        // Decide initial status based on author role
        $initialStatus = (strtolower((string) $viewer->role) === 'student') ? 'revised' : 'open';

        // Create thread
        $thread = Comment::create([
            'version_id'     => $version->id,
            'author_id'      => $viewer->id,
            'status'         => $initialStatus,
            'selection_text' => $data['selection_text'] ?? null,
        ]);

        // Seed first message
        CommentMessage::create([
            'comment_id' => $thread->id,
            'author_id'  => $viewer->id,
            'body'       => trim($data['body']),
        ]);

        // Log
        CommentEvent::create([
            'comment_id'   => $thread->id,
            'triggered_by' => $viewer->id,
            'event'        => 'created',
        ]);

        // Return JSON for AJAX callers; keeps the UI on-page
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
     * Show a single thread inside the unified workspace view.
     * Auto-transition: if the STUDENT views an "open" thread → mark as "seen".
     * Also supports returning a PARTIAL when ?partial=1 or AJAX request (for the side panel).
     */
    public function show(Request $request, string $type, int $thread)
    {
        abort_unless(in_array($type, ['exhibition', 'essay']), 404);

        $viewer    = Auth::user();
        $studentId = $request->query('student');

        if (!$viewer && !$studentId) {
            return redirect('/login');
        }

        // Load thread + relationships needed for ownership check
        $activeThread = Comment::with([
            'author:id,name',
            'anchor',
            'version.submission', // needed for canViewThread()
            'messages' => fn ($q) => $q->with('author:id,name')->orderBy('created_at', 'asc'),
            'events'   => fn ($q) => $q->with('triggeredBy:id,name')->orderBy('created_at', 'asc'),
        ])->findOrFail($thread);

        // 🔒 Authorization: viewer must be teacher/admin OR owning student
        abort_unless($this->canViewThread($viewer, $activeThread), 403, 'Access denied.');

        $latestVersion = $activeThread->version;
        $submission    = $latestVersion->submission;

        // Display student in header based on actual submission owner (robust against spoofed ?student=)
        $studentDisplay = $submission && $submission->student_id
            ? User::find($submission->student_id)
            : ($studentId ? User::find((int) $studentId) : $viewer);

        // Latest general comment for this version (optional)
        $general = GeneralComment::where('version_id', $latestVersion->id)
            ->with('author:id,name')
            ->latest()
            ->first();

        // Auto-transition: student viewing an "open" thread ⇒ seen
        if (
            $viewer &&
            strtolower((string) $viewer->role) === 'student' &&
            (int) $viewer->id === (int) ($submission->student_id ?? 0) &&
            ($activeThread->status === 'open')
        ) {
            $activeThread->status = 'seen';
            $activeThread->save();

            CommentEvent::create([
                'comment_id'   => $activeThread->id,
                'triggered_by' => $viewer->id,
                'event'        => 'status:seen',
            ]);

            // Refresh events in-memory for the view (optional)
            $activeThread->load([
                'events' => fn ($q) => $q->with('triggeredBy:id,name')->orderBy('created_at', 'asc'),
            ]);
        }

        // Style map for status pills (used both in full + partial views)
        $colors = [
            'open'     => ['#e8f1ff', '#0a2e6c'],
            'seen'     => ['#f5f5f5', '#444'],
            'revised'  => ['#fff4e5', '#8a5a00'],
            'approved' => ['#e6ffed', '#135f26'],
            'closed'   => ['#ddd', '#333'],
        ];

        // All threads for this submission (newest first) so the LIST can render
        $threads = Comment::whereHas('version', function ($q) use ($submission) {
                $q->where('submission_id', $submission->id);
            })
            ->with('author:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        // If asked for partial (side panel fetch), return only the thread block
        if ($request->boolean('partial') || $request->ajax()) {
            return response()->view('partials.thread', [
                'type'          => $type,
                'student'       => $studentDisplay,
                'submission'    => $submission,
                'latestVersion' => $latestVersion,
                'thread'        => $activeThread,
                'threads'       => $threads,
                'colors'        => $colors,
            ]);
        }

        // Otherwise render full workspace page
        return view('workspace', [
            'type'          => $type,
            'student'       => $studentDisplay,
            'submission'    => $submission,
            'latestVersion' => $latestVersion,
            'thread'        => $activeThread,   // opens the right pane in thread mode
            'threads'       => $threads,        // list populated
            'colors'        => $colors,         // status pill colors
            'general'       => $general,
        ]);
    }

    /**
     * Post a reply; auto-transitions status depending on the author.
     * - If STUDENT replies: open|seen → revised
     * - If TEACHER replies: revised|seen → open
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

        // Need version->submission for ownership check
        $comment = Comment::with('version.submission')->findOrFail($thread);

        // 🔒 Authorization: must be teacher/admin OR owning student
        abort_unless($this->canViewThread($viewer, $comment), 403, 'Access denied.');

        // Create reply
        CommentMessage::create([
            'comment_id' => $comment->id,
            'author_id'  => $viewer->id,
            'body'       => trim($data['body']),
        ]);

        CommentEvent::create([
            'comment_id'   => $comment->id,
            'triggered_by' => $viewer->id,
            'event'        => 'replied',
        ]);

        // Determine if author is the student for this submission
        // (Use actual submission owner for robustness)
        $isStudentAuthor = strtolower((string) $viewer->role) === 'student'
            && (int) $viewer->id === (int) ($comment->version->submission->student_id ?? 0);

        $newStatus = null;

        if ($isStudentAuthor) {
            // Student reply ⇒ revised (awaiting teacher)
            if (in_array($comment->status, ['open', 'seen'], true)) {
                $newStatus = 'revised';
            }
        } else {
            // Teacher/Admin reply ⇒ open (awaiting student)
            if (in_array($comment->status, ['revised', 'seen'], true)) {
                $newStatus = 'open';
            }
        }

        if ($newStatus && $newStatus !== $comment->status) {
            $comment->status = $newStatus;
            $comment->save();

            CommentEvent::create([
                'comment_id'   => $comment->id,
                'triggered_by' => $viewer->id,
                'event'        => 'status:' . $newStatus,
            ]);
        }

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

        // Need ownership check
        $threadModel = Comment::with(['messages.author:id,name', 'version.submission'])->findOrFail($thread);

        // 🔒 Authorization: must be teacher/admin OR owning student
        if (!$this->canViewThread($viewer, $threadModel)) {
            return response()->json(['ok' => false, 'error' => 'forbidden'], 403);
        }

        // Use actual owner for rendering consistency
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
     * Manually set status (teacher/admin control).
     * Allowed: open | seen | revised | approved | closed
     */
    public function setStatus(Request $request, string $type, int $thread)
    {
        abort_unless(in_array($type, ['exhibition', 'essay']), 404);

        $viewer    = Auth::user();
        $studentId = $request->query('student');

        if (!$viewer && !$studentId) {
            return redirect('/login');
        }

        // Need version->submission for ownership check
        $comment = Comment::with('version.submission')->findOrFail($thread);

        // 🔒 Authorization: must be teacher/admin AND able to view the thread
        $role = strtolower((string) $viewer->role);
        abort_unless(in_array($role, ['teacher', 'admin'], true), 403, 'Only teachers/admins can change status.');
        abort_unless($this->canViewThread($viewer, $comment), 403, 'Access denied.');

        $allowed = ['open', 'seen', 'revised', 'approved', 'closed'];

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', $allowed)],
        ]);

        $oldStatus = $comment->status;
        $newStatus = $data['status'];

        if ($oldStatus !== $newStatus) {
            $comment->status = $newStatus;
            $comment->save();

            CommentEvent::create([
                'comment_id'   => $comment->id,
                'triggered_by' => $viewer->id,
                'event'        => 'status:' . $newStatus,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => $comment->status]);
        }

        return redirect()->route('thread.show', [
            'type'    => $type,
            'thread'  => $comment->id,
            'student' => $studentId,
        ])->with('ok', 'Status ' . ($oldStatus === $newStatus ? 'unchanged' : 'updated to “' . ucfirst($newStatus) . '”') . '.');
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
        $comment = Comment::with(['messages.author:id,name', 'version.submission'])->findOrFail($thread);
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
}