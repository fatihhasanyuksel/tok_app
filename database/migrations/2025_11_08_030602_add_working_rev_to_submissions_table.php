<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Only add the column if it doesn't already exist
        if (!Schema::hasColumn('submissions', 'working_rev')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->unsignedBigInteger('working_rev')->default(0)->after('updated_at');
            });
        }
        // (Optional) Index can be added later if needed; skipping to avoid duplicate-index errors.
    }

    public function down(): void
    {
        if (Schema::hasColumn('submissions', 'working_rev')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->dropColumn('working_rev');
            });
        }
    }
};