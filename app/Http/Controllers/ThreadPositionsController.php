<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Thread;

class ThreadPositionsController extends Controller
{
    /**
     * PATCH /api/threads/{thread}/positions
     * Body: { pm_from: int, pm_to: int }
     */
    public function update(Request $request, Thread $thread)
    {
        $data = $request->validate([
            'pm_from' => ['required', 'integer', 'min:0'],
            'pm_to'   => ['required', 'integer', 'min:0'],
        ]);

        if ($data['pm_to'] < $data['pm_from']) {
            [$data['pm_from'], $data['pm_to']] = [$data['pm_to'], $data['pm_from']];
        }

        $thread->pm_from = $data['pm_from'];
        $thread->pm_to   = $data['pm_to'];
        $thread->save();

        return response()->json([
            'ok'       => true,
            'threadId' => $thread->id,
            'pm_from'  => $thread->pm_from,
            'pm_to'    => $thread->pm_to,
        ]);
    }
}