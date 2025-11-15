<?php

namespace App\Http\Controllers\Tok;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Submission;
use App\Models\Version;

class RichTextController extends Controller
{
    /**
     * GET /api/tok/docs/{owner_type}/{owner_id}
     * Return the current working draft for this workspace.
     */
    public function show(Request $request, string $owner_type, string|int $owner_id)
    {
        $submission = $this->resolveSubmission($owner_type, $owner_id, $request);
        if (!$submission) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json([
            'submission_id' => $submission->id,
            'type'          => $submission->type,
            'student_id'    => $submission->student_id,
            'body_plain'    => (string) $submission->working_body,
            'body_html'     => (string) $submission->working_html,
            'updated_at'    => $submission->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * PATCH|POST /api/tok/docs/{owner_type}/{owner_id}
     * Save working draft (autosave). Also creates a lightweight Version snapshot.
     * Handles JSON, form-encoded, and Beacon (text/plain or text/html) payloads.
     */
    public function autosave(Request $request, string $owner_type, string|int $owner_id)
    {
        $submission = $this->resolveSubmission($owner_type, $owner_id, $request, createIfMissing: true);
        if (!$submission) {
            return response()->json(['error' => 'Not found'], 404);
        }

        // ---- Content-Type–aware payload handling (PATCH or POST, Beacon-safe) ----
        $ct   = strtolower((string) $request->header('content-type', ''));
        $raw  = $request->getContent();
        $plain = '';
        $html  = '';

        if (str_starts_with($ct, 'application/json')) {
            $data  = json_decode($raw ?: '[]', true) ?: [];
            $plain = (string) ($data['plain'] ?? $request->input('plain') ?? $request->input('body') ?? '');
            $html  = (string) ($data['html']  ?? $request->input('html')  ?? $request->input('body_html') ?? '');
        } elseif (str_starts_with($ct, 'text/plain') || str_starts_with($ct, 'text/html')) {
            // Beacon or raw body: frontend sends the HTML string as the entire request body
            $plain = '';
            $html  = (string) $raw;
        } else {
            // Form-encoded (the older way)
            $plain = (string) ($request->input('plain') ?? $request->input('body') ?? '');
            $html  = (string) ($request->input('html')  ?? $request->input('body_html') ?? '');
        }

        $html = trim($html);

        // If editor sent entity-escaped HTML, decode it
        if ($html !== '' && strpos($html, '<') === false && stripos($html, '&lt;') !== false) {
            $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (strpos($decoded, '<') !== false) {
                $html = $decoded;
            }
        }

        // Fallback: build simple <br> HTML from plain text
        if ($html === '' && $plain !== '') {
            $safePlain = htmlspecialchars($plain, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html = nl2br($safePlain, false);
        }

        // ---- Persist working copy + lightweight history snapshot ----
        $submission->working_body = $plain;
        $submission->working_html = $html;
        $submission->status       = $submission->status ?: 'draft';
        $submission->save();

        Version::create([
            'submission_id'  => $submission->id,
            'body_plain'     => $plain,
            'body_html'      => $html,
            'is_milestone'   => false,
            'milestone_note' => null,
        ]);

        return response()->noContent(); // 204
    }

    /**
     * POST /api/tok/docs/{owner_type}/{owner_id}/commit
     * Record a milestone snapshot (e.g., manual save/“Save Draft”).
     */
    public function commit(Request $request, string $owner_type, string|int $owner_id)
    {
        $submission = $this->resolveSubmission($owner_type, $owner_id, $request, createIfMissing: true);
        if (!$submission) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $plain = (string) (
            $request->input('plain') ??
            $request->input('body')  ??
            $submission->working_body ??
            ''
        );

        $html = trim((string) (
            $request->input('html') ??
            $request->input('body_html') ??
            $submission->working_html ??
            ''
        ));

        if ($html !== '' && strpos($html, '<') === false && stripos($html, '&lt;') !== false) {
            $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (strpos($decoded, '<') !== false) {
                $html = $decoded;
            }
        }

        if ($html === '' && $plain !== '') {
            $safePlain = htmlspecialchars($plain, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html = nl2br($safePlain, false);
        }

        // Mirror working copy and record a milestone
        $submission->working_body = $plain;
        $submission->working_html = $html;
        $submission->status       = $submission->status ?: 'draft';
        $submission->save();

        Version::create([
            'submission_id'  => $submission->id,
            'body_plain'     => $plain,
            'body_html'      => $html,
            'is_milestone'   => true,
            'milestone_note' => $request->input('milestone_note'),
        ]);

        return response()->json(['ok' => true, 'submission_id' => $submission->id]);
    }

    /**
     * Resolve a Submission from owner_type/owner_id.
     * - If owner_id matches an existing submission id, use it.
     * - Otherwise treat owner_id as a student_id for the given type.
     * - Optionally create if missing (for autosave/commit).
     */
    private function resolveSubmission(string $owner_type, string|int $owner_id, Request $request, bool $createIfMissing = false): ?Submission
    {
        $type = strtolower($owner_type);
        if (!in_array($type, ['exhibition', 'essay'], true)) {
            return null;
        }

        // 1) Try by submission id
        if (is_numeric($owner_id)) {
            $byId = Submission::where('id', (int)$owner_id)->first();
            if ($byId) {
                return $byId;
            }
        }

        // 2) Treat owner_id as student_id
        $studentId = is_numeric($owner_id) ? (int)$owner_id : null;

        // If not numeric, allow falling back to the viewer (student opening own workspace)
        if (!$studentId) {
            $viewer = Auth::user();
            if ($viewer && strtolower((string)$viewer->role) === 'student') {
                $studentId = (int)$viewer->id;
            }
        }

        if (!$studentId) {
            return null;
        }

        $existing = Submission::where('student_id', $studentId)
                              ->where('type', $type)
                              ->first();
        if ($existing) {
            return $existing;
        }

        if ($createIfMissing) {
            return Submission::create([
                'student_id'   => $studentId,
                'type'         => $type,
                'status'       => 'draft',
                'working_body' => '',
                'working_html' => '',
            ]);
        }

        return null;
    }
}