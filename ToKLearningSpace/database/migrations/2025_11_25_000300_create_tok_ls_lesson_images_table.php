<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tok_ls_lesson_images', function (Blueprint $table) {
            $table->id();

            // Each record = "this lesson uses this image file"
            $table->unsignedBigInteger('lesson_id');

            // Storage path relative to "public" disk, e.g.
            // "tok-ls/23/lesson-images/8221c1b5f8211ad504c4dd421c93a5ff4fc16b81d.webp"
            $table->string('path', 255);

            $table->timestamps();

            // FK to lessons; if a lesson is deleted, mappings go too
            $table->foreign('lesson_id')
                ->references('id')
                ->on('tok_ls_lessons')
                ->onDelete('cascade');

            $table->index('lesson_id');
            $table->index('path');

            // A given lesson only needs one row per image path
            $table->unique(['lesson_id', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tok_ls_lesson_images');
    }
};