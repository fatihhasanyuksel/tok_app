<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('threads', function (Blueprint $table) {
            if (!Schema::hasColumn('threads', 'pm_from')) {
                $table->unsignedInteger('pm_from')->nullable()->after('end_offset');
            }
            if (!Schema::hasColumn('threads', 'pm_to')) {
                $table->unsignedInteger('pm_to')->nullable()->after('pm_from');
            }
            $table->index(['pm_from', 'pm_to'], 'threads_pm_range_idx');
        });
    }

    public function down(): void {
        Schema::table('threads', function (Blueprint $table) {
            // Drop index first if it exists
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_map('strtolower', array_keys($sm->listTableIndexes('threads')));
            if (in_array('threads_pm_range_idx', $indexes, true)) {
                $table->dropIndex('threads_pm_range_idx');
            }

            if (Schema::hasColumn('threads', 'pm_from')) {
                $table->dropColumn('pm_from');
            }
            if (Schema::hasColumn('threads', 'pm_to')) {
                $table->dropColumn('pm_to');
            }
        });
    }
};