<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // Plain-text offsets at the time of creation (left pane selection)
            if (!Schema::hasColumn('comments', 'start_offset')) {
                $table->unsignedInteger('start_offset')->nullable()->after('selection_text');
            }
            if (!Schema::hasColumn('comments', 'end_offset')) {
                $table->unsignedInteger('end_offset')->nullable()->after('start_offset');
            }

            // ProseMirror positions (will be backfilled & maintained)
            if (!Schema::hasColumn('comments', 'pm_from')) {
                $table->unsignedInteger('pm_from')->nullable()->after('end_offset');
            }
            if (!Schema::hasColumn('comments', 'pm_to')) {
                $table->unsignedInteger('pm_to')->nullable()->after('pm_from');
            }

            // Ensure boolean flag exists for resolved
            if (!Schema::hasColumn('comments', 'is_resolved')) {
                $table->boolean('is_resolved')->default(false)->after('pm_to');
            }
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            if (Schema::hasColumn('comments', 'is_resolved'))  $table->dropColumn('is_resolved');
            if (Schema::hasColumn('comments', 'pm_to'))        $table->dropColumn('pm_to');
            if (Schema::hasColumn('comments', 'pm_from'))      $table->dropColumn('pm_from');
            if (Schema::hasColumn('comments', 'end_offset'))   $table->dropColumn('end_offset');
            if (Schema::hasColumn('comments', 'start_offset')) $table->dropColumn('start_offset');
        });
    }
};