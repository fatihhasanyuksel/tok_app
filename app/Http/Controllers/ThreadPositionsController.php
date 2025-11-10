<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Thread;

class ThreadPositionsController extends Controller
{
    /**
     * PATCH /api/threads/{thread}/positions
     * Body: { pm_from: int, pm_to: int }
     *
     * If the thread id is missing, invalid, or not found,
     * return 204 No Content instead of throwing a 404.
     */
    public function update(Request $request, $thread = null)
    {
        // 1️⃣ Early exit for invalid thread identifiers
        if ($thread === null || $thread === '' || $thread === 'undefined' || $thread === 'null') {
            return response()->noContent(); // 204
        }

        // 2️⃣ If it's not numeric, quietly ignore
        if (!ctype_digit((string)$thread)) {
            return response()->noContent(); // 204
        }

        // 3️⃣ Try to find the thread — if not found, no-op
        $model = Thread::find((int)$thread);
        if (!$model) {
            return response()->noContent(); // 204
        }

        // 4️⃣ Validate inputs only if keys are present
        $data = $request->validate([
            'pm_from' => ['nullable', 'integer', 'min:0'],
            'pm_to'   => ['nullable', 'integer', 'min:0'],
        ]);

        // Nothing meaningful sent? → quietly return 204
        if (!array_key_exists('pm_from', $data) && !array_key_exists('pm_to', $data)) {
            return response()->noContent();
        }

        // 5️⃣ Normalize reversed positions
        if (isset($data['pm_from'], $data['pm_to']) && $data['pm_to'] < $data['pm_from']) {
            [$data['pm_from'], $data['pm_to']] = [$data['pm_to'], $data['pm_from']];
        }

        // 6️⃣ Save updates if provided
        if (array_key_exists('pm_from', $data)) {
            $model->pm_from = $data['pm_from'];
        }
        if (array_key_exists('pm_to', $data)) {
            $model->pm_to = $data['pm_to'];
        }

        $model->save();

        // 7️⃣ Always return quiet success (no content)
        return response()->noContent(); // 204
    }
}