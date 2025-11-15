<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Submission;

class SubmissionController extends Controller
{
    /**
     * PATCH /api/tok/docs/{type}/{id}
     * Handles autosave updates for a submission.
     */
    public function update(Request $request, string $type, int $id)
    {
        $request->validate([
            'html' => ['required', 'string'],
            'rev'  => ['nullable', 'integer', 'min:1'], // revision (optional when flag off)
        ]);

        /** @var \App\Models\User|null $user */
        $user = $request->user();

        /** @var Submission $sub */
        $sub = Submission::where('type', $type)->findOrFail($id);

        // ───────────────
        // FLAG OFF → keep current legacy behavior
        // ───────────────
        if (!config('tok.autosave_rev')) {
            $sub->working_html = $request->string('html');
            $sub->updated_by   = optional($user)->id;
            $sub->save();

            return response()->json([
                'ok'  => true,
                'rev' => $sub->working_rev,
            ]);
        }

        // ───────────────
        // FLAG ON → optimistic concurrency guard
        // ───────────────
        $clientRev = (int) $request->input('rev', 0);
        if ($clientRev < 1) {
            return response()->json([
                'ok' => false,
                'error' => 'REV_REQUIRED',
                'message' => 'Missing or invalid revision. Reload before saving.',
                'server_rev' => $sub->working_rev,
            ], 400);
        }

        // Atomic compare-and-swap (update only if rev matches)
        $affected = DB::table('submissions')
            ->where('id', $sub->id)
            ->where('working_rev', $clientRev)
            ->update([
                'working_html' => $request->string('html'),
                'working_rev'  => DB::raw('working_rev + 1'),
                'updated_by'   => optional($user)->id,
                'updated_at'   => now(),
            ]);

        if ($affected === 0) {
            // Another client already saved → return latest version
            $fresh = Submission::find($sub->id, ['working_html','working_rev']);
            return response()->json([
                'ok'          => false,
                'conflict'    => true,
                'error'       => 'STALE_REV',
                'message'     => 'Your copy is stale. Reload before saving.',
                'server_rev'  => $fresh->working_rev,
                'server_html' => $fresh->working_html,
            ], 409);
        }

        // Success → return the new revision number
        $new = Submission::find($sub->id, ['working_rev']);
        return response()->json([
            'ok'  => true,
            'rev' => $new->working_rev,
        ]);
    }
}