<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

use App\Models\User;
use App\Models\Submission;
use App\Models\Version;
use App\Models\GeneralComment;

class FeedbackController extends Controller
{
    /** Reuse everywhere */
    private const TYPES = ['exhibition', 'essay', 'submission'];

    /**
     * GET /workspace/{type}
     * Renders the two-pane workspace and populates the right-pane thread list.
     */
    public function workspace(Request $request, string $type)
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

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
        'created_by'    => $viewer->id ?? null,
    ]);
}
        
// Determine initial HTML for hydration — always use rich HTML
$initialHtml = (string) ($latestVersion->body_html ?? '<p><em>Start writing…</em></p>');

        // Build thread list for the right pane (eager-load what the UI needs)
        $threads = \App\Models\Comment::whereHas('version', function ($q) use ($submission) {
                $q->where('submission_id', $submission->id);
            })
            ->with([
                'author:id,name',
                // Avoid ambiguous columns in latestOfMany join
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

        // Match via students.email -> teacher_id -> teachers.name
        $studentEmail   = data_get($student, 'email');
        $teacherId      = DB::table('students')->where('email', $studentEmail)->value('teacher_id');
        $supervisorLabel = $teacherId
            ? (DB::table('teachers')->where('id', $teacherId)->value('name') ?? 'Unassigned')
            : 'Unassigned';

// --- Choose workspace view: default to V3, allow V2 only if flag + query param ---
$allowV2 = filter_var(env('WORKSPACE_ALLOW_V2', false), FILTER_VALIDATE_BOOLEAN);
$useV2   = $allowV2 && $request->boolean('v2');

$data = [
    'type'            => $type,
    'student'         => $student,
    'submission'      => $submission,
    'latestVersion'   => $latestVersion,
    'thread'          => null,
    'threads'         => $threads,
    'general'         => $general,
    'supervisorLabel' => $supervisorLabel,
    'initialHtml'     => $initialHtml,
    'role'            => strtolower((string) ($viewer->role ?? Auth::user()->role ?? 'guest')),
];

return view($useV2 ? 'workspace' : 'workspace_v3', $data);
    }

    // inside app/Http/Controllers/FeedbackController.php

    /**
     * POST /workspace/{type}/save
     * 	“Students: autosave only”
     * “Staff: manual save / snapshot”
     * Autosave (no snapshot) + revision handshake.
     * - Client sends { autosave: true, submission_id, body, body_html, rev }
     * - Server compares rev vs submissions.working_rev
     *   - If mismatch: 409 + { expected: current_rev, submission_id, version_id }
     *   - If match: update version, ++working_rev, return { ok, rev, submission_id, version_id, snapshot:false }
     */
public function saveDraft(Request $request, string $type)
{
    abort_unless(in_array($type, ['exhibition', 'essay'], true), 404);

    $user = $request->user();
    abort_unless($user, 401);

    $role      = strtolower((string) $user->role);
    $isStudent = ($role === 'student');
    $isStaff   = in_array($role, ['teacher', 'admin'], true);

    // Validate incoming payload
    $data = $request->validate([
        'autosave'      => ['boolean'],
        'submission_id' => ['nullable', 'integer', 'min:1'],
        'body'          => ['nullable', 'string', 'max:100000'], // plain text, optional
        'body_html'     => ['required', 'string'],               // TipTap HTML
        'rev'           => ['nullable', 'integer', 'min:0'],
        'snapshot'      => ['sometimes', 'boolean'],
    ]);

    $isSnapshot = $request->boolean('snapshot');

    // Students never manually save — only autosave allowed
    if ($isStudent && !$request->boolean('autosave')) {
        return response()->json([
            'ok'    => false,
            'error' => 'manual_save_disabled_for_students',
        ], 403);
    }

    // (Optional hard guard: students cannot trigger snapshot)
    if ($isStudent && $isSnapshot) {
        return response()->json([
            'ok'    => false,
            'error' => 'snapshot_not_allowed_for_students',
        ], 403);
    }

    // Resolve the student + submission
    if ($isStudent) {
        $studentId  = (int) $user->id;
        $submission = \App\Models\Submission::firstOrCreate(
            ['student_id' => $studentId, 'type' => $type],
            ['status' => 'draft', 'working_rev' => 1]
        );
    } else {
        $sid = (int) ($data['submission_id'] ?? 0);
        abort_if($sid <= 0, 404, 'Missing submission id.');
        $submission = \App\Models\Submission::with('latestVersion')->findOrFail($sid);

        // Ensure working_rev is initialised
        if ((int) ($submission->working_rev ?? 0) <= 0) {
            $submission->working_rev = 1;
            $submission->save();
        }
    }

    // Ensure a working version exists
    $version = $submission->latestVersion()->first();
    if (!$version) {
        $version = \App\Models\Version::create([
            'submission_id' => $submission->id,
            'body_html'     => '<p><em>Start writing…</em></p>',
            'files_json'    => [],
            'created_by'    => $user->id ?? null,
        ]);
    }

    // --- Revision handshake (now shared by autosave + snapshot) ---
    $clientRev = (int) ($data['rev'] ?? 0);
    $serverRev = (int) ($submission->working_rev ?? 1);

    // If client is behind, ask them to sync (no write)
    if ($clientRev !== $serverRev) {
        return response()->json([
            'ok'            => false,
            'error'         => 'conflict',
            'expected'      => $serverRev,
            'submission_id' => $submission->id,
            'version_id'    => $version->id,
        ], 409);
    }

    // Normalise HTML → plain once
    $bodyHtml = (string) ($data['body_html'] ?? '');
    $bodyText = (string) ($data['body'] ?? '');
    if ($bodyText === '') {
        // Use our helper so export + working_body are consistent
        $bodyText = $this->htmlToPlain($bodyHtml);
    }

    // --- Snapshot path (staff, manual) ---
    if ($isSnapshot && $isStaff) {
        // Create a read-only version snapshot
        $summary = mb_substr(
            trim(preg_replace('/\s+/', ' ', strip_tags($bodyHtml))),
            0,
            120
        );

        $snapshotVersion = \App\Models\Version::create([
            'submission_id' => $submission->id,
            'body'          => $bodyText,
            'body_html'     => $bodyHtml,
            'summary'       => $summary,
            'created_by'    => $user->id ?? null,
        ]);

        // Mirror into working_* so editor always rehydrates with the latest snapshot
        $submission->working_html = $bodyHtml;
        $submission->working_body = $bodyText;
        $submission->working_rev  = $serverRev + 1;
        $submission->save();

        return response()->json([
            'ok'            => true,
            'snapshot'      => true,
            'submission_id' => $submission->id,
            'version_id'    => $snapshotVersion->id,
            'rev'           => (int) $submission->working_rev,
        ]);
    }

    // --- Autosave path (students only) ---
    // Update current working draft (no new Version snapshot here)
    $version->body_html = $bodyHtml;
    $version->save();

    // Mirror into submission so hydration uses exact rich HTML (images persist)
    $submission->working_html = $bodyHtml;
    $submission->working_body = $bodyText;
    $submission->working_rev  = $serverRev + 1;
    $submission->save();

    return response()->json([
        'ok'            => true,
        'rev'           => (int) $submission->working_rev,
        'submission_id' => (int) $submission->id,
        'version_id'    => (int) $version->id,
        'snapshot'      => false,
    ]);
}

    /**
     * GET /workspace/{type}/history
     * Returns the list of version snapshots for the submission (JSON when requested).
     */
    public function history(Request $request, string $type)
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

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
    ->with('author:id,name,role')          // 👈 include who created the version
    ->orderBy('created_at','desc')
    ->get()
    ->map(function ($v) {
        $plain   = strip_tags((string)($v->body_html ?? ''));
        $summary = mb_substr(trim(preg_replace('/\s+/', ' ', $plain)), 0, 120);

        return [
            'id'               => $v->id,
            'created_at'       => $v->created_at,
            'created_at_human' => optional($v->created_at)->diffForHumans(),
            'created_at_full'  => optional($v->created_at)->format('Y-m-d H:i'),
            'body_html'        => $v->body_html,
            'summary'          => $summary,
            'by_role'          => strtolower(optional($v->author)->role ?? 'student'),
            'by_name'          => (string) (optional($v->author)->name ?? 'Student'),
        ];
    });

if ($request->wantsJson()) {
    return response()->json(['ok' => true, 'versions' => $versions]);
}

        // Non-JSON fallback
        return response()->json(['ok' => true, 'versions' => $versions]);
    }

    /**
     * POST /workspace/{type}/restore/{version}
     * Restores the selected snapshot into the editor (updates working_* mirrors).
     */
    public function restore(Request $request, string $type, int $version)
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

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
     */
    public function export(Request $request, string $type)
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

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
                'latestMessage' => function ($q) { $q->select('comment_messages.*'); },
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

    // -----------------------------------------------------------------------------
    // Version preview
    // -----------------------------------------------------------------------------
    public function showVersion(Request $request, string $type, int $versionId)
    {
        abort_unless(in_array($type, ['exhibition','essay'], true), 404);

        $viewer = Auth::user();
        if (!$viewer) return redirect('/login');

        $v = \App\Models\Version::with('submission:id,student_id,type')
            ->where('id', $versionId)
            ->firstOrFail();

        abort_unless(optional($v->submission)->type === $type, 404);

        $student = null;
        if ($v->submission && $v->submission->student_id) {
            $student = \App\Models\User::find($v->submission->student_id);
        }

        $title = 'Version #'.$v->id.' — '.ucfirst($type).' — '.($student->name ?? 'Student');
        $esc = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

        return response()->make(
            '<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>'.$esc($title).'</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:#111;margin:24px;line-height:1.5}
  h1{margin:0 0 12px}
  .meta{color:#666;margin-bottom:12px}
  .box{border:1px solid #e5e7eb;border-radius:12px;padding:16px;background:#fff}
  .actions{margin:12px 0 20px;display:flex;gap:10px;align-items:center}
  .btn{padding:8px 12px;border:1px solid #ddd;border-radius:10px;background:#fff;cursor:pointer}
  @media print{a[href]::after{content:""} body{margin:0}}
</style>
</head>
<body>
  <h1>'.$esc($title).'</h1>
  <div class="actions">
    <button id="wk3-restore" class="btn">Restore this version</button>
    <small id="wk3-restore-status" style="color:#666;"></small>
  </div>
  <div class="meta">Saved: '.($v->created_at ? $v->created_at->format('Y-m-d H:i') : '').'</div>
  <div class="box">'.$v->body_html.'</div>

  <script>
  (function () {
    var btn = document.getElementById("wk3-restore");
    var status = document.getElementById("wk3-restore-status");
    if (!btn) return;

    btn.addEventListener("click", async function () {
      if (!confirm("Restore this version over the working draft?")) return;
      status.textContent = "Restoring…";

      try {
        const res = await fetch("/workspace/'. $type .'/restore/'. $v->id .'", {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": "'. csrf_token() .'"
          },
          body: JSON.stringify({})
        });

        if (!res.ok) {
          let msg = "";
          try { msg = await res.text(); } catch (e) {}
          status.textContent = "Error: " + (msg || ("HTTP " + res.status));
          return;
        }

        status.textContent = "Restored. Redirecting…";
        location.href = "/workspace/'. $type .'";
      } catch (e) {
        console.error(e);
        status.textContent = "Network error";
      }
    });
  })();
  </script>
</body>
</html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }
    
private function htmlToPlain(string $html): string
{
    $s = preg_replace('~<\s*br\s*/?\s*>~i', "\n", $html);
    $s = preg_replace('~</\s*(p|div|li|h[1-6]|blockquote)\s*>~i', "\n", $s);
    $s = strip_tags($s);
    $s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
    $s = str_replace("\xC2\xA0", ' ', $s);
    $s = preg_replace("/[ \t]+/", ' ', $s);
    $s = preg_replace("/\r\n|\r|\n{3,}/", "\n\n", $s);
    return trim($s);
}
/**
 * Handle image uploads from TipTap editor.
 * POST /workspace/{type}/upload
 */
public function upload(Request $request, string $type)
{
    // Accept either "image" or "file" (some toolbars send "file")
    $request->validate([
        'image' => 'sometimes|image|max:4096',
        'file'  => 'sometimes|image|max:4096',
    ]);

    $uploaded = $request->file('image') ?? $request->file('file');
    if (!$uploaded) {
        return response()->json(['error' => 'No image uploaded'], 422);
    }

    $user = Auth::user();
    $studentId = $user->id ?? 'guest';

    $hash = sha1_file($uploaded->getRealPath());
    $ext  = strtolower($uploaded->getClientOriginalExtension());
    $safeExt = in_array($ext, ['jpg','jpeg','png','webp','gif']) ? $ext : 'jpg';

    $dir  = "public/tok/{$studentId}/images";
    $path = "{$dir}/{$hash}.{$safeExt}";

    if (!Storage::exists($dir)) {
        Storage::makeDirectory($dir);
    }

    if (!Storage::exists($path)) {
        // Read → orient → scale down to max width 1600 (keep aspect)
        $img = Image::read($uploaded->getRealPath())
            ->orient()
            ->scale(width: 1600);

        // Encode by chosen extension
        switch ($safeExt) {
            case 'webp': $binary = $img->toWebp(85); break;
            case 'png':  $binary = $img->toPng();    break; // lossless
            case 'jpg':
            case 'jpeg':
            default:     $binary = $img->toJpeg(85);
        }

        Storage::put($path, (string) $binary);
    }

    return response()->json(['url' => Storage::url($path)]);
}
}