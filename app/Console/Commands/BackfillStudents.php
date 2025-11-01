<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillStudents extends Command
{
    protected $signature = 'tok:backfill-students {--apply : Write changes (omit for dry-run)}';
    protected $description = 'Backfill users (role=student) from legacy students table, carrying teacher_id and names.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        // Guards
        if (!DB::getSchemaBuilder()->hasTable('students')) {
            $this->warn('No `students` table found. Nothing to backfill.');
            return self::SUCCESS;
        }
        if (!DB::getSchemaBuilder()->hasTable('users')) {
            $this->error('`users` table missing. Aborting.');
            return self::FAILURE;
        }

        // Columns sanity
        $hasTeacherIdOnUsers = DB::getSchemaBuilder()->hasColumn('users', 'teacher_id');
        if (!$hasTeacherIdOnUsers) {
            $this->error('`users.teacher_id` not found. Run the migration from Step 1 first.');
            return self::FAILURE;
        }

        $students = DB::table('students')->orderBy('id')->get();
        if ($students->isEmpty()) {
            $this->info('`students` is empty. Nothing to do.');
            return self::SUCCESS;
        }

        $created = 0; $updated = 0; $skippedNoEmail = 0; $touchedIds = [];

        $this->line(($apply ? 'APPLY' : 'DRY-RUN') . ': scanning ' . $students->count() . ' legacy students…');
        foreach ($students as $s) {
            $email = trim((string)($s->email ?? ''));
            if ($email === '') {
                $skippedNoEmail++;
                $this->warn("  - students#{$s->id} skipped (no email).");
                continue;
            }

            $first = trim((string)($s->first_name ?? ''));
            $last  = trim((string)($s->last_name ?? ''));
            $name  = trim($first . ' ' . $last);
            if ($name === '') {
                $name = $first ?: $last ?: 'Student';
            }

            // Find existing user by email
            $user = DB::table('users')->where('email', $email)->first();

            if (!$user) {
                // Create new user
                $payload = [
                    'name'       => $name,
                    'email'      => $email,
                    'role'       => 'student',
                    'teacher_id' => $s->teacher_id ?? null,
                    'password'   => bcrypt(Str::random(24)), // placeholder; admin can reset
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($apply) {
                    $uid = DB::table('users')->insertGetId($payload);
                    $touchedIds[] = $uid;
                }

                $created++;
                $this->info("  + create users (email={$email}) role=student teacher_id=" . ($s->teacher_id ?? 'null'));
                continue;
            }

            // Existing user: update role/name/teacher_id if needed (but never downgrade teacher/admin)
            $changes = [];
            if (empty($user->role) || $user->role === 'student') {
                if ($user->role !== 'student')        $changes['role'] = 'student';
                if ($user->name !== $name)            $changes['name'] = $name;
                if (($user->teacher_id ?? null) !== ($s->teacher_id ?? null)) $changes['teacher_id'] = $s->teacher_id ?? null;
            } else {
                // If user is teacher/admin, we won’t change role or name; only set teacher_id when it’s null (rare)
                if (($user->teacher_id ?? null) !== ($s->teacher_id ?? null) && $s->teacher_id) {
                    $changes['teacher_id'] = $s->teacher_id;
                }
            }

            if (!empty($changes)) {
                $changes['updated_at'] = now();
                if ($apply) {
                    DB::table('users')->where('id', $user->id)->update($changes);
                    $touchedIds[] = $user->id;
                }
                $updated++;
                $this->info("  ~ update users#{$user->id} (email={$email}) " . json_encode($changes));
            } else {
                $this->line("  = keep users#{$user->id} (email={$email})");
            }
        }

        // Summary
        $this->newLine();
        $this->table(
            ['created', 'updated', 'skipped_no_email', 'total_students'],
            [[ $created, $updated, $skippedNoEmail, $students->count() ]]
        );

        if (!$apply) {
            $this->comment('Dry-run only. Re-run with --apply to write these changes:');
            $this->comment('  php artisan tok:backfill-students --apply');
        } else {
            $this->info('Backfill applied. Touched user IDs: ' . (empty($touchedIds) ? 'none' : implode(',', $touchedIds)));
        }

        return self::SUCCESS;
    }
}