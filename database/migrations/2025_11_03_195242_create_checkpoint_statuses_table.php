<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('checkpoint_statuses', function (Blueprint $table) {
            $table->id();

            // who the status is about
            $table->unsignedBigInteger('student_id')->index();

            // 'exhibition' | 'essay'
            $table->string('type', 24)->index();

            // machine code like: none|draft1|draft2|draft3|student_final|approved
            $table->string('status_code', 48)->index();

            // optional short note
            $table->string('note', 255)->nullable();

            // who set it & when
            $table->unsignedBigInteger('selected_by')->nullable()->index();
            $table->timestamp('selected_at')->nullable();

            $table->timestamps();

            // one “latest” row per (student,type)
            $table->unique(['student_id', 'type'], 'uniq_student_type_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoint_statuses');
    }
};