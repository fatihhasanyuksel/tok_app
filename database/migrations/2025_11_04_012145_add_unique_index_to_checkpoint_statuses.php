<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkpoint_statuses', function (Blueprint $table) {
            // Prevent duplicate entries for same student & work type
            $table->unique(['student_id', 'type'], 'chk_status_student_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('checkpoint_statuses', function (Blueprint $table) {
            $table->dropUnique('chk_status_student_type_unique');
        });
    }
};