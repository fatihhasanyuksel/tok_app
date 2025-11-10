<?php

namespace App\Http\Controllers\Tok;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

use App\Models\Submission;
use App\Models\Comment; // your “thread” model; rename if different

class DocController extends Controller
{
    /**
     * Legacy-compatible autosave.
     * - No 409s.
     * - Saves html/plain and bumps working_rev.
     */
    public function patch(Request $request, string $ownerType, int $ownerId): JsonResponse
    {
        $sub = $this->findSubmission($ownerType, $ownerId);
        $this->authorizeEdit($sub);

        [$html, $plain] = $this->validatedPayload($request);

        // Persist
        $sub->working_html = $html;
        if ($plain !== null) {
            $sub->working_body = $plain;
        }
        // always bump a monotonic working_rev for traceability
        $sub->working_rev = max(1, (int)($sub->working_rev ?? 0)) + 1;
        $sub->save();

        return response()->json([
            'ok'          => true,
            'rev'         => (int) $sub->working_rev,
            'working_rev' => (int) $sub->working_rev,
        ]);
    }

    /**
     * Revision-aware autosave.
     * - If client rev != server rev → 409 with server copy so the UI can replace local.
     * - On success, increments working_rev and returns the new rev.
     */
    public function patchRev(Request $request, string $ownerType, int $ownerId): JsonResponse
    {
        $sub = $this->findSubmission($ownerType, $ownerId);
        $this->authorizeEdit($sub);

        [$html, $plain, $clientRev] = $this->validatedPayload($request, true);

        $serverRev = (int) ($sub->working_rev ?? 1);

        // If client provided a rev and it doesn't match server → conflict
        if ($clientRev !== null && $clientRev !== $serverRev) {
            return response()->json([
                'ok'          => false,
                'conflict'    => true,
                'server_rev'  => $serverRev,
                'server_html' => (string) ($sub->working_html ?? ''),
            ], 409);
        }

        // Save and bump revision
        $sub->working_html = $html;
        if ($plain !== null) {
            $sub->working_body = $plain;
        }
        $sub->working_rev = max(1, $serverRev) + 1;
        $sub->save();

        return response()->json([
            'ok'          => true,
            'rev'         => (int) $sub->working_rev,
            'working_rev' => (int) $sub->working_rev,
        ]);
    }

    /**
     * Persist ProseMirror absolute positions for a thread highlight.
     * Body: { pm_from:int, pm_to:int }
     */
    public function positions(Request $request, int $thread): JsonResponse
    {
        $t = Comment::query()->findOrFail($thread);
        $this->authorizeThread($t);

        $data = $request->validate([
            'pm_from' => ['required', 'integer', 'min:0'],
            'pm_to'   => ['required', 'integer', 'min:0'],
        ]);

        // normalize (ensure pm_to >= pm_from)
        $from = min($data['pm_from'], $data['pm_to']);
        $to   = max($data['pm_from'], $data['pm_to']);

        $t->pm_from = $from;
        $t->pm_to   = $to;
        $t->save();

        return response()->json(['ok' => true]);
    }

    // ----------------- helpers -----------------

    /**
     * Map {ownerType, ownerId} to a Submission row.
     * If you have multiple owner types, adapt the lookup accordingly.
     */
    protected function findSubmission(string $ownerType, int $ownerId): Submission
    {
        // If you actually key only by ID, ownerType is metadata; that’s fine.
        // Otherwise, add ->where('owner_type', $ownerType)
        return Submission::query()
            ->where('id', $ownerId)
            ->firstOrFail();
    }

    /**
     * Basic authorization gate:
     * - teachers/admin can edit anything
     * - students can edit their own submission (student_id/user_id match)
     * Adjust to your real relationships/policies if you already have them.
     */
    protected function authorizeEdit(Submission $sub): void
    {
        $u = Auth::user();
        if (!$u) abort(401);

        $role = Str::of((string)($u->role ?? ''))->lower()->value();

        $isStaff = in_array($role, ['teacher', 'admin'], true);
        $owns    = (int)($sub->student_id ?? $sub->user_id ?? 0) === (int)$u->id;

        if (!$isStaff && !$owns) {
            abort(403, 'You are not allowed to edit this submission.');
        }
    }

    protected function authorizeThread(Comment $t): void
    {
        $u = Auth::user();
        if (!$u) abort(401);

        $role = Str::of((string)($u->role ?? ''))->lower()->value();
        $isStaff = in_array($role, ['teacher', 'admin'], true);

        // Allow staff universally; students only if the thread belongs to their submission
        if ($isStaff) return;

        $studentId = (int)($t->student_id ?? $t->submission->student_id ?? 0);
        if ($studentId !== (int)$u->id) {
            abort(403, 'You are not allowed to modify this thread.');
        }
    }

    /**
     * Pull, trim and sanity-check payload from request.
     * When $wantRev = true, also returns client rev as int|null.
     *
     * @return array{0:string,1:?string,2:?int}
     */
    protected function validatedPayload(Request $request, bool $wantRev = false): array
    {
        $html  = (string) ($request->input('html', '') ?? '');
        $plain = $request->has('plain') ? (string) ($request->input('plain') ?? '') : null;

        // very light size guard (prevent accidental megadumps); tweak as needed
        if (strlen($html) > 2_000_000) {
            abort(413, 'Document too large.');
        }

        $rev = null;
        if ($wantRev && $request->filled('rev')) {
            $rev = (int) $request->input('rev');
        }

        return [$html, $plain, $rev];
    }
}