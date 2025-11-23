<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tok_ls_lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('teacher_id');
            $table->string('title')->nullable();
            $table->longText('lesson_content')->nullable(); // TipTap content (HTML/JSON)
            $table->timestamps();

            $table->foreign('class_id')
                ->references('id')
                ->on('tok_ls_classes')
                ->onDelete('cascade');

            $table->foreign('teacher_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tok_ls_lessons');
    }
};