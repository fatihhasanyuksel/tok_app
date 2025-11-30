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
        Schema::table('tok_ls_lessons', function (Blueprint $table) {
            // Text fields so teachers can write multi-line objectives & criteria
            $table->text('objectives')->nullable()->after('title');
            $table->text('success_criteria')->nullable()->after('objectives');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tok_ls_lessons', function (Blueprint $table) {
            $table->dropColumn(['objectives', 'success_criteria']);
        });
    }
};