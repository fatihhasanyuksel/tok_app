<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only drop if it exists (safe on prod)
        if (Schema::hasColumn('comments', 'status')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }

    public function down(): void
    {
        // Recreate column if you ever need to roll back
        if (! Schema::hasColumn('comments', 'status')) {
            Schema::table('comments', function (Blueprint $table) {
                // Keep a conservative default so old UIs (if any) don’t explode
                $table->string('status', 20)->default('open')->after('author_id');
            });
        }
    }
};