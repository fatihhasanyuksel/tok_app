<?php

namespace App\Http\Controllers;

use Mews\Purifier\Facades\Purifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Models\User;
use App\Models\Submission;
use App\Models\Version;
use App\Models\GeneralComment;

class FeedbackController extends Controller
{
    /**
     * GET /workspace/{type}
     * Renders the two-pane workspace and populates the right-pane thread list.
     */
    public function workspace(Request $request, string $type)
    {
        abort_unless(in_array($type, ['exhibition','essay'], true), 404);

        $viewer = Auth::user();
        if (!$viewer) return redirect('/login');

        // Whose workspace?
        if (strtolower((string) $viewer->role) === 'student') {
            $student = $viewer;
        } else {
            $sid = (int) $request->query('student', 0);
            $student = $sid > 0
                ? User::where('id', $sid)->where('role', 'student')->first()
                : null;
            $student = $student ?: $viewer; // fallback
        }

        // Ensure submission exists for this student+type
        $submission = Submission::firstOrCreate(
            ['student_id' => $student->id, 'type' => $type],
            ['status' => 'draft']
        );

        // Ensure a latest version exists (for threads/history panels)
        $latestVersion = $submission->latestVersion()->first();
        if (!$latestVersion) {
            $latestVersion = Version::create([
                'submission_id' => $submission->id,
                'body_html'     => '<p><em>Start writing…</em></p>',
                'files_json'    => [],
            ]);
        }

        // Build thread list for the right pane (eager-load what the UI needs)
        $threads = \App\Models\Comment::whereHas('version', function ($q) use ($submission) {
                $q->where('submission_id', $submission->id);
            })
            ->with([
    'author:id,name',
    // Important: let Eloquent select from comment_messages explicitly to avoid ambiguous columns
    'latestMessage' => function ($q) {
        $q->select('comment_messages.*');
    },
    'version.submission:id,student_id',
])
            ->orderBy('created_at', 'desc')
            ->get();

        // Optional: latest general message shown above editor
        $general = GeneralComment::where('version_id', $latestVersion->id)
            ->with('author:id,name')
            ->latest()
            ->first();

        return view('workspace', [
            'type'          => $type,
            'student'       => $student,
            'submission'    => $submission,
            'latestVersion' => $latestVersion,
            'thread'        => null,
            'threads'       => $threads,
            'general'       => $general,
        ]);
    }

    /**
     * POST /workspace/{type}/save
     * - Autosave: updates working_body + working_html (no snapshot).
     * - Manual save (button): creates a Version snapshot using TipTap HTML.
     *   If "milestone" is checked (staff only), flags it as a milestone.
     */
    public function saveDraft(Request $request, string $type)
    {
        abort_unless(in_array($type, ['exhibition','essay'], true), 404);

        $viewer = Auth::user();
        if (!$viewer) return redirect('/login');

        // Resolve student (students → self, staff → ?student=)
        if (strtolower((string) $viewer->role) === 'student') {
            $student = $viewer;
        } else {
            $sid = (int) $request->query('student', 0);
            $student = $sid > 0
                ? User::where('id', $sid)->where('role', 'student')->first()
                : null;
            $student = $student ?: $viewer;
        }

        // Accept both fields; body_html may be absent on older forms
        $data = $request->validate([
            'body'           => ['required','string','min:0'],
            'body_html'      => ['sometimes','string'],
            'milestone'      => ['sometimes','boolean'],
            'milestone_note' => ['sometimes','nullable','string','max:140'],
        ]);

        $submission = Submission::firstOrCreate(
            ['student_id' => $student->id, 'type' => $type],
            ['status' => 'draft']
        );

        $isAutosave = (bool) $request->boolean('autosave');

        // Pull both fields
        $plain = (string) ($data['body'] ?? '');
        $html  = (string) $request->input('body_html', '');

        // If TipTap HTML wasn't sent (rare), build a minimal HTML fallback from plain
        if ($html === '' && $plain !== '') {
            $html = nl2br(e($plain), false);
        }

        // AUTOSAVE → sanitize and mirror into working_* only
        if ($isAutosave) {
            $clean = Purifier::clean($html, 'tok');

            $submission->working_html = $clean;
            $submission->working_body = $this->htmlToPlain($clean);
            $submission->save();

            return $request->wantsJson()
                ? response()->json(['ok' => true, 'mode' => 'autosave'])
                : back()->with('ok', 'Autosaved.');
        }

        // MANUAL SAVE → sanitize and create a Version snapshot
        $clean = Purifier::clean($html, 'tok');

        // Build attributes for the new Version snapshot
        $attrs = [
            'submission_id' => $submission->id,
            'body_html'     => $clean,
            'files_json'    => [],
        ];

        // Only staff can mark milestones on manual save
        $isStaff = in_array(strtolower((string)$viewer->role), ['teacher','admin'], true);
        if ($isStaff && $request->boolean('milestone')) {
            $attrs['is_milestone'] = true;
            $note = trim((string) $request->input('milestone_note', ''));
            if ($note !== '') {
                $attrs['milestone_note'] = $note;
            }
        }

        $version = Version::create($attrs);

        // Keep editor in sync with what was just saved (store both HTML and plain)
        $submission->working_html = $clean;
        $submission->working_body = $this->htmlToPlain($clean);
        $submission->save();

        if ($request->wantsJson()) {
            return response()->json([
                'ok'             => true,
                'mode'           => 'snapshot',
                'version_id'     => $version->id,
                'body_html'      => $version->body_html,
                'body_plain'     => $this->htmlToPlain($version->body_html),
                'created_at'     => $version->created_at,
                'created_human'  => optional($version->created_at)->diffForHumans(),
                'is_milestone'   => (bool) ($version->is_milestone ?? false),
                'milestone_note' => $version->milestone_note ?? null,
            ]);
        }
        return back()->with('ok', 'Draft saved.');
    }

    /**
     * GET /workspace/{type}/history
     * Returns the list of version snapshots for the submission (JSON when requested).
     */
    public function history(Request $request, string $type)
    {
        abort_unless(in_array($type, ['exhibition','essay']), true);

        $viewer = Auth::user();
        if (!$viewer) return redirect('/login');

        // Same resolution logic as workspace
        if (strtolower((string) $viewer->role) === 'student') {
            $student = $viewer;
        } else {
            $sid = (int) $request->query('student', 0);
            $student = $sid > 0
                ? User::where('id', $sid)->where('role', 'student')->first()
                : null;
            $student = $student ?: $viewer;
        }

        $submission = Submission::firstOrCreate(
            ['student_id' => $student->id, 'type' => $type],
            ['status' => 'draft']
        );

        $versions = Version::where('submission_id', $submission->id)
            ->orderBy('created_at','desc')
            ->get()
            ->map(function ($v) {
                return [
                    'id'               => $v->id,
                    'created_at'       => $v->created_at,
                    'created_at_human' => optional($v->created_at)->diffForHumans(),
                    'body_html'        => $v->body_html,
                    'body_plain'       => $this->htmlToPlain($v->body_html),
                    'is_milestone'     => (bool) ($v->is_milestone ?? false),
                    'milestone_note'   => $v->milestone_note ?? null,
                ];
            });

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'versions' => $versions]);
        }

        // Non-JSON fallback
        return view('history', [
            'type'     => $type,
            'versions' => $versions,
        ]);
    }

    /**
     * POST /workspace/{type}/restore/{version}
     * Restores the selected snapshot into the editor (updates working_* mirrors).
     */
    public function restore(Request $request, string $type, int $version)
    {
        abort_unless(in_array($type, ['exhibition','essay'], true), 404);

        $viewer = Auth::user();
        if (!$viewer) return redirect('/login');

        // Resolve student same as before
        if (strtolower((string) $viewer->role) === 'student') {
            $student = $viewer;
        } else {
            $sid = (int) $request->query('student', 0);
            $student = $sid > 0
                ? User::where('id', $sid)->where('role', 'student')->first()
                : null;
            $student = $student ?: $viewer;
        }

        $submission = Submission::firstOrCreate(
            ['student_id' => $student->id, 'type' => $type],
            ['status' => 'draft']
        );

        $v = Version::where('submission_id', $submission->id)
            ->where('id', $version)
            ->firstOrFail();

        $plain = $this->htmlToPlain($v->body_html ?? '');

        // Update both mirrors so the editor rehydrates with formatting/images
        $submission->working_body = $plain;
        $submission->working_html = $v->body_html ?? '';
        $submission->save();

        return response()->json([
            'ok'         => true,
            'version_id' => $v->id,
            'body_html'  => $v->body_html,   // TipTap will use this to rehydrate with images
            'body_plain' => $plain,          // Hidden textarea mirror
        ]);
    }

    /**
     * GET /workspace/{type}/export
     * Staff-only: print-friendly HTML of the student's work.
     * - latest *working* text preferred
     * - version history with milestone badges
     * - feedback threads with messages
     * Add ?download=1 to force download.
     * Add ?cache=1 to save a copy to storage/app/exports/.
     */
    public function export(Request $request, string $type)
    {
        abort_unless(in_array($type, ['exhibition','essay'], true), 404);

        $viewer = Auth::user();
        if (!$viewer) return redirect('/login');

        // Staff-only (students can export their own work too)
        $isStaff = in_array(strtolower((string) $viewer->role), ['teacher','admin'], true);
        abort_unless($isStaff || strtolower((string)$viewer->role) === 'student', 403);

        // Resolve student (teachers/admins may pass ?student=). If none, allow student self-export.
        $sid = (int) $request->query('student', 0);
        $student = $sid > 0
            ? User::where('id', $sid)->where('role', 'student')->first()
            : null;

        if (!$student && strtolower((string)$viewer->role) === 'student') {
            $student = $viewer;
        }

        abort_unless($student && strtolower((string)$student->role) === 'student', 404);

        // Ensure submission exists
        $submission = Submission::firstOrCreate(
            ['student_id' => $student->id, 'type' => $type],
            ['status' => 'draft']
        );

        // Prefer the true latest working text; fallback to latest snapshot HTML → plain
        $workingPlain = (string) ($submission->working_body ?? '');
        if ($workingPlain === '') {
            $latestVersion = $submission->latestVersion()->first();
            $latestHtml = $latestVersion?->body_html ?? '';
            $workingPlain = $this->htmlToPlain($latestHtml);
        }
        // Decode entities once so quotes render as characters in export
        $workingPlain = html_entity_decode($workingPlain, ENT_QUOTES, 'UTF-8');

        // Versions (for milestones)
        $versions = Version::where('submission_id', $submission->id)
            ->orderBy('created_at', 'asc')
            ->get();

        // Threads + messages (eager-load for derived label)
        $threads = \App\Models\Comment::whereHas('version', function ($q) use ($submission) {
                $q->where('submission_id', $submission->id);
            })
            ->with([
    'author:id,name',
    'messages' => function ($q) { $q->orderBy('created_at', 'asc'); },
    'messages.author:id,name',
    // Important: fully-qualify the select so latestOfMany join has no ambiguity
    'latestMessage' => function ($q) {
        $q->select('comment_messages.*');
    },
    'version.submission:id,student_id',
])
            ->orderBy('created_at', 'asc')
            ->get();

        // Prepare simple, print-friendly HTML
        $title = 'Export — ' . ucfirst($type) . ' — ' . ($student->name ?? 'Student');
        $download = $request->boolean('download');

        $esc = function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };
        $nl2br = function ($s) use ($esc) {
            return nl2br($esc($s));
        };

        ob_start();
        ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= $esc($title) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; color:#111; margin:24px; }
  h1,h2,h3 { margin: 0 0 8px; }
  .muted { color:#666; }
  .box { border:1px solid #e5e7eb; border-radius:12px; padding:14px; margin:14px 0; }
  .badge { display:inline-block; padding:2px 8px; border-radius:999px; background:#f6f8ff; border:1px solid #e5eaf0; font-size:12px; color:#0a2e6c; }
  .pill-ms { background:#fff4e5; border-color:#ffe7ba; color:#8a5a00; }
  .msg { border-top:1px dashed #e5e5e5; padding-top:10px; margin-top:10px; }
  .sel { background:#fff7a8; border:1px solid #f2e38b; border-radius:8px; padding:6px 8px; margin:6px 0; }
  .grid { display:grid; grid-template-columns: 1fr; gap:12px; }
  @media print {
    a[href]::after { content:""; }
    .no-print { display:none; }
    body { margin:0; }
  }
</style>
</head>
<body>
  <div class="no-print" style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
    <h1><?= $esc($title) ?></h1>
    <span class="muted">Generated: <?= date('Y-m-d H:i') ?></span>
  </div>

  <div class="box">
    <h2>Student</h2>
    <div><?= $esc($student->name) ?> · <span class="muted"><?= $esc($student->email) ?></span></div>
    <div>Type: <strong><?= $esc(ucfirst($type)) ?></strong></div>
  </div>

  <div class="box">
    <h2>Latest Work (plain)</h2>
    <pre style="white-space:pre-wrap; margin:0;"><?= $esc($workingPlain) ?></pre>
  </div>

  <div class="box">
    <h2>Version History</h2>
    <?php if (!$versions->count()): ?>
      <div class="muted">No snapshots.</div>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($versions as $v): ?>
          <div style="border:1px solid #eee; border-radius:10px; padding:10px;">
            <div>
              <strong>#<?= (int)$v->id ?></strong>
              <span class="muted"> · <?= $esc(optional($v->created_at)->format('Y-m-d H:i')) ?></span>
              <?php if ($v->is_milestone): ?>
                <span class="badge pill-ms" title="<?= $esc($v->milestone_note ?? 'Milestone') ?>">⭐ Milestone</span>
              <?php endif; ?>
            </div>
            <?php
              $plain = strip_tags(preg_replace('~<\s*br\s*/?\s*>~i', "\n", (string)$v->body_html));
              $plain = str_replace("\xC2\xA0", ' ', trim($plain));
              $plain = html_entity_decode($plain, ENT_QUOTES, 'UTF-8');
            ?>
            <div class="muted" style="margin-top:6px;"><?= $esc(mb_strimwidth($plain, 0, 600, '…')) ?></div>
            <?php if ($v->is_milestone && $v->milestone_note): ?>
              <div style="margin-top:6px;"><em>Note:</em> <?= $esc($v->milestone_note) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="box">
    <h2>Feedback Threads</h2>
    <?php if (!$threads->count()): ?>
      <div class="muted">No feedback threads.</div>
    <?php else: ?>
      <?php foreach ($threads as $t): ?>
        <?php
          // Derive label without legacy statuses
          $ownerId      = optional(optional($t->version)->submission)->student_id;
          $lastAuthorId = optional($t->latestMessage)->author_id;

          if ($t->is_resolved) {
              $label = 'Resolved';
          } elseif ($lastAuthorId && $ownerId && (int)$lastAuthorId === (int)$ownerId) {
              $label = 'Awaiting Teacher';
          } else {
              $label = 'Awaiting Student';
          }
        ?>
        <div style="border:1px solid #eee; border-radius:10px; padding:10px; margin:10px 0;">
          <div>
            <strong>Thread #<?= (int)$t->id ?></strong>
            <span class="muted"> · <?= $esc($label) ?> · <?= $esc(optional($t->created_at)->format('Y-m-d H:i')) ?></span>
          </div>
          <?php if (!empty($t->selection_text)): ?>
            <div class="sel"><em>“<?= $esc($t->selection_text) ?>”</em></div>
          <?php endif; ?>
          <?php if ($t->messages && $t->messages->count()): ?>
            <?php foreach ($t->messages as $m): ?>
              <div class="msg">
                <div style="font-weight:600;"><?= $esc(optional($m->author)->name ?? 'User') ?></div>
                <div class="muted" style="font-size:12px;"><?= $esc(optional($m->created_at)->format('Y-m-d H:i')) ?></div>
                <div style="margin-top:6px;"><?= $nl2br($m->body ?? '') ?></div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="muted">No messages.</div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</body>
</html>
        <?php
        $html = (string) ob_get_clean();

        // ---------- Auto-named filename ----------
        $safeType    = ucfirst($type);
        $safeStudent = preg_replace('/[^A-Za-z0-9\-\._]+/', '-', (string)($student->name ?? 'Student'));
        $timestamp   = date('Y-m-d_H-i-s');
        $filename    = "TOK_{$safeType}_{$safeStudent}_{$timestamp}.html";

        // Optional on-disk cache: /storage/app/exports/...
        if ($request->boolean('cache')) {
            Storage::makeDirectory('exports');
            Storage::put("exports/{$filename}", $html);
        }

        $disposition = $download ? 'attachment' : 'inline';

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', $disposition . '; filename="' . $filename . '"');
    }

    /**
     * Helpers
     */
    private function plainToHtml(string $text): string
    {
        // Normalize newlines
        $text = str_replace(["\r\n","\r"], "\n", $text);
        // Split into paragraphs by blank line
        $parts = preg_split('/\n{2,}/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (!$parts) return '<p></p>';

        $html = [];
        foreach ($parts as $p) {
            $p = e($p);
            // Convert single newlines to <br> within a paragraph
            $p = nl2br($p, false);
            $html[] = "<p>{$p}</p>";
        }
        return implode("\n", $html);
    }

    private function htmlToPlain(?string $html): string
    {
        if (!$html) return '';

        // 1) Convert <br> to newlines so line breaks are preserved after stripping tags
        $html = preg_replace('~<\s*br\s*/?\s*>~i', "\n", $html);

        // 2) Decode HTML entities up to 2 passes
        $decoded = $html;
        for ($i = 0; $i < 2; $i++) {
            $next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($next === $decoded) break;
            $decoded = $next;
        }

        // 3) Strip tags
        $plain = trim(strip_tags($decoded));

        // 4) Normalize whitespace: NBSP and CRLFs
        $plain = str_replace("\xC2\xA0", ' ', $plain);
        $plain = str_replace(["\r\n", "\r"], "\n", $plain);

        return $plain;
    }
}