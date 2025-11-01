<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('student_moods', function (Blueprint $t) {
            $t->id();

            // Student who set the mood
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            // Optional link to a submission (e.g., exhibition or essay)
            $t->foreignId('submission_id')->nullable()->constrained('submissions')->cascadeOnDelete();

            // Mood choices
            $t->enum('mood', ['confident', 'calm', 'uncertain', 'stressed']);

            $t->timestamps();

            $t->index(['student_id', 'submission_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('student_moods');
    }
};