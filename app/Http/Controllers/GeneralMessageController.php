<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GeneralMessageController extends Controller
{
    /**
     * GET /workspace/{type}/general/{submission}
     * Return JSON: { ok: true, messages: [...] }
     */
    public function index(Request $request, string $type, int $submission)
    {
        try {
            $this->authorizeAccess($type, $submission);

            // Detect column name at runtime (sender_id vs user_id)
            $hasSenderId = $this->tableHasColumn('general_messages', 'sender_id');
            $hasUserId   = $this->tableHasColumn('general_messages', 'user_id');

            $senderExpr = $hasSenderId && $hasUserId
                ? DB::raw('COALESCE(gm.sender_id, gm.user_id) as sender_id')
                : ($hasSenderId
                    ? DB::raw('gm.sender_id as sender_id')
                    : DB::raw('gm.user_id as sender_id'));

            $rows = DB::table('general_messages as gm')
                ->select([
                    'gm.id',
                    'gm.submission_id',
                    $senderExpr,
                    'gm.body',
                    'gm.created_at',
                ])
                ->where('gm.submission_id', $submission)
                ->orderBy('gm.id')
                ->get();

            return response()->json([
                'ok'       => true,
                'messages' => $rows,
            ], 200);
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json(['ok' => false, 'error' => 'unauthenticated'], 401);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage() ?: 'error'], $e->getStatusCode());
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'server'], 500);
        }
    }

    /**
     * POST /workspace/{type}/general/{submission}
     * Body: { body: "text" }
     */
    public function store(Request $request, string $type, int $submission)
    {
        try {
            $this->authorizeAccess($type, $submission);

            $data = $request->validate([
                'body' => ['required', 'string', 'min:1', 'max:4000'],
            ]);

            $uid = Auth::id() ?: optional(Auth::user())->id;
            if (!$uid) {
                return response()->json(['ok' => false, 'error' => 'unauthenticated'], 401);
            }

            // Detect columns
            $hasSenderId = $this->tableHasColumn('general_messages', 'sender_id');
            $hasUserId   = $this->tableHasColumn('general_messages', 'user_id');

            $insert = [
                'submission_id' => $submission,
                'body'          => $data['body'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            if ($hasSenderId) $insert['sender_id'] = $uid;
            if ($hasUserId)   $insert['user_id']   = $uid;
            if (!$hasSenderId && !$hasUserId) {
                $insert['user_id'] = $uid;
            }

            // Insert the message
            $id = DB::table('general_messages')->insertGetId($insert);

            // Optional mark as read — never fail the request for this
            if ($this->tableExists('general_message_reads')) {
                try {
                    DB::table('general_message_reads')->updateOrInsert(
                        ['message_id' => $id, 'user_id' => $uid],
                        ['read_at' => now(), 'created_at' => now(), 'updated_at' => now()]
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('general_message_reads write failed', [
                        'message_id' => $id,
                        'user_id'    => $uid,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'ok'   => true,
                'id'   => $id,
                'time' => now()->toIso8601String(),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['ok' => false, 'error' => 'validation', 'messages' => $e->errors()], 422);
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json(['ok' => false, 'error' => 'unauthenticated'], 401);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage() ?: 'error'], $e->getStatusCode());
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'server'], 500);
        }
    }

    /**
     * GET /workspace/{type}/general/{submission}/unread
     * Return JSON: { unread: N }
     */
    public function unread(Request $request, string $type, int $submission)
    {
        try {
            $this->authorizeAccess($type, $submission);

            $uid = Auth::id();
            if (!$uid) return response()->json(['unread' => 0], 200);

            if (!$this->tableExists('general_message_reads')) {
                return response()->json(['unread' => 0], 200);
            }

            $hasSenderId = $this->tableHasColumn('general_messages', 'sender_id');
            $hasUserId   = $this->tableHasColumn('general_messages', 'user_id');

            $q = DB::table('general_messages as gm')
                ->leftJoin('general_message_reads as r', function ($j) use ($uid) {
                    $j->on('r.message_id', '=', 'gm.id')
                      ->where('r.user_id', '=', $uid);
                })
                ->where('gm.submission_id', $submission)
                ->whereNull('r.id');

            if ($hasSenderId && $hasUserId) {
                $q->whereRaw('COALESCE(gm.sender_id, gm.user_id) <> ?', [$uid]);
            } elseif ($hasSenderId) {
                $q->where('gm.sender_id', '<>', $uid);
            } else {
                $q->where('gm.user_id', '<>', $uid);
            }

            $count = (int) $q->count();

            return response()->json(['unread' => $count], 200);
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json(['unread' => 0], 200);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['unread' => 0], 200);
        } catch (\Throwable $e) {
            return response()->json(['unread' => 0], 200);
        }
    }

    // ----------------- helpers -----------------

    private function authorizeAccess(string $type, int $submission): void
    {
        if (!in_array($type, ['exhibition', 'essay'], true)) {
            abort(404, 'invalid-type');
        }

        $u = Auth::user();
        if (!$u) abort(401, 'unauthenticated');

        $exists = DB::table('submissions')
            ->where('id', $submission)
            ->where('type', $type)
            ->exists();

        if (!$exists) abort(404, 'not-found');
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        try {
            return DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}