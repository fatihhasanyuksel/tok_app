<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Only add the column if it doesn't already exist
        if (! Schema::hasColumn('tok_ls_responses', 'teacher_feedback')) {
            Schema::table('tok_ls_responses', function (Blueprint $table) {
                $table->text('teacher_feedback')->nullable();
            });
        }
    }

    public function down(): void
    {
        // ✅ Only drop if it exists (safe rollback)
        if (Schema::hasColumn('tok_ls_responses', 'teacher_feedback')) {
            Schema::table('tok_ls_responses', function (Blueprint $table) {
                $table->dropColumn('teacher_feedback');
            });
        }
    }
};