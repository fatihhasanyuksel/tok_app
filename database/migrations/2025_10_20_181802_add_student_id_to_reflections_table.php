<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reflections', function (Blueprint $table) {
            // Add a nullable foreign key to link each reflection to a student
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reflections', function (Blueprint $table) {
            // Drop the foreign key if this migration is rolled back
            $table->dropConstrainedForeignId('student_id');
        });
    }
};