<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Submission;
use App\Models\Version;

class StudentStartController extends Controller
{
    public function start(Request $request, string $type)
    {
        abort_unless(in_array($type, ['exhibition', 'essay']), 404);

        $user = Auth::user();
        if (!$user) {
            return redirect('/login')->with('ok', 'Please log in first.');
        }

        // Ensure submission exists
        $submission = Submission::firstOrCreate(
            ['student_id' => $user->id, 'type' => $type],
            ['status' => 'draft']
        );

        // Ensure version exists
        $version = $submission->latestVersion()->first()
            ?: Version::create([
                'submission_id' => $submission->id,
                'body_html'     => '<p><em>Start writing…</em></p>',
                'files_json'    => [],
            ]);

        // Redirect straight to workspace
        return redirect()->route('workspace.show', ['type' => $type])
            ->with('ok', ucfirst($type) . ' workspace ready.');
    }
}