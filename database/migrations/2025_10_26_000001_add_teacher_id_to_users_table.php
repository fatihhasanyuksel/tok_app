<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add teacher_id only if it doesn't exist
        if (!Schema::hasColumn('users', 'teacher_id')) {
            Schema::table('users', function (Blueprint $table) {
                // nullable because teachers/admins won't have a teacher_id
                $table->unsignedBigInteger('teacher_id')->nullable()->after('role')->index();
            });

            // Add a guarded FK to users.id if possible (ignore if it fails)
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('teacher_id')
                          ->references('id')->on('users')
                          ->nullOnDelete(); // if a teacher user is deleted, unlink students
                });
            } catch (\Throwable $e) {
                // If FK creation fails due to existing data/state, keep going.
            }
        }

        // Ensure an index exists for fast listing of students by teacher
        try {
            Schema::table('users', function (Blueprint $table) {
                // Some hosts may not allow naming check, harmless to attempt
                $table->index(['role', 'teacher_id'], 'users_role_teacher_idx');
            });
        } catch (\Throwable $e) {
            // ignore if index already exists
        }
    }

    public function down(): void
    {
        // Rollback is safe: drop FK if exists, then column
        if (Schema::hasColumn('users', 'teacher_id')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropForeign(['teacher_id']);
                });
            } catch (\Throwable $e) { /* ignore */ }

            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropIndex('users_role_teacher_idx');
                });
            } catch (\Throwable $e) { /* ignore */ }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('teacher_id');
            });
        }
    }
};