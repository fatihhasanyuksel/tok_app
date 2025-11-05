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
    public function save(Request $request, string $type)
    {
        abort_unless(in_array($type, ['exhibition','essay'], true), 404);

        $viewer = Auth::user();
        if (!$viewer) return redirect('/login');

        // Resolve student
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

        // Source of truth from the form
        $plain = (string) $request->input('body', '');
        $html  = (string) $request->input('body_html', '');
        $html  = trim($html);

        // Decode entity-escaped HTML if needed
        if ($html !== '' && strpos($html, '<') === false && stripos($html, '&lt;') !== false) {
            $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (strpos($decoded, '<') !== false) {
                $html = $decoded;
            }
        }

        // Fallback to <br>-ified plain text
        if ($html === '' && $plain !== '') {
            $safePlain = htmlspecialchars($plain, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html = nl2br($safePlain, false);
        }

        // Save working draft
        $submission->working_body = $plain;
        $submission->working_html = $html;
        $submission->status       = 'draft';
        $submission->save();

        // Version snapshot
        Version::create([
            'submission_id'  => $submission->id,
            'body_plain'     => $plain,
            'body_html'      => $html,
            'is_milestone'   => $request->boolean('milestone'),
            'milestone_note' => $request->input('milestone_note'),
        ]);

        return back()->with('ok', 'Draft saved.');
    }
}