<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Only add if it doesn't exist already
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('student')->after('password');
            });
        }
        // Optionally ensure an index to speed role filters (ignore if it exists)
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->index('role', 'users_role_idx');
            });
        } catch (\Throwable $e) { /* ignore */ }
    }

    public function down(): void
    {
        // Drop index if present
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_role_idx');
            });
        } catch (\Throwable $e) { /* ignore */ }

        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};