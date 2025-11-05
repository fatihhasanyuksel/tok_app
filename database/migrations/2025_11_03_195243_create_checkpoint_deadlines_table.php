<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('checkpoint_deadlines', function (Blueprint $table) {
            $table->id();

            // 'exhibition' | 'essay'
            $table->string('type', 24)->index();

            // stage matching statuses (e.g., draft1, draft2, draft3, student_final, approved)
            $table->string('stage_code', 48)->index();

            // global due date/time (Asia/Dubai via app timezone)
            $table->dateTime('due_at');

            // who set it
            $table->unsignedBigInteger('created_by')->nullable()->index();

            $table->timestamps();

            // one global deadline per (type, stage)
            $table->unique(['type', 'stage_code'], 'uniq_type_stage_deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoint_deadlines');
    }
};