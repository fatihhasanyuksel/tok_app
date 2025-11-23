<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tok_ls_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lesson_id');
            $table->unsignedBigInteger('student_id');
            $table->longText('student_response')->nullable();   // TipTap JSON/HTML
            $table->longText('teacher_feedback')->nullable();   // TipTap JSON/HTML
            $table->timestamps();

            $table->foreign('lesson_id')
                ->references('id')
                ->on('tok_ls_lessons')
                ->onDelete('cascade');

            $table->foreign('student_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tok_ls_responses');
    }
};