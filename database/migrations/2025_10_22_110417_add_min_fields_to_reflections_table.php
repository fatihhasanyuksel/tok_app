<?php
// database/migrations/2025_10_22_150000_add_min_fields_to_reflections_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('reflections')) {
            Schema::table('reflections', function (Blueprint $table) {
                if (!Schema::hasColumn('reflections', 'teacher_id')) {
                    $table->unsignedBigInteger('teacher_id')->index()->after('id');
                }
                if (!Schema::hasColumn('reflections', 'title')) {
                    $table->string('title', 200)->default('Untitled')->after('teacher_id');
                }
                if (!Schema::hasColumn('reflections', 'body')) {
                    $table->text('body')->nullable()->after('title');
                }
                if (!Schema::hasColumn('reflections', 'student_id')) {
                    $table->unsignedBigInteger('student_id')->nullable()->index()->after('body');
                }
                if (!Schema::hasColumn('reflections', 'status')) {
                    $table->string('status', 30)->default('draft')->index()->after('student_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reflections')) {
            Schema::table('reflections', function (Blueprint $table) {
                if (Schema::hasColumn('reflections', 'status'))     $table->dropColumn('status');
                if (Schema::hasColumn('reflections', 'student_id'))  $table->dropColumn('student_id');
                if (Schema::hasColumn('reflections', 'body'))        $table->dropColumn('body');
                if (Schema::hasColumn('reflections', 'title'))       $table->dropColumn('title');
                // keep teacher_id since index page already uses it
            });
        }
    }
};