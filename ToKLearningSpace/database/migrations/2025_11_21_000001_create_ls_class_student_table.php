<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ls_class_student', function (Blueprint $table) {
            $table->id();

            // Link to tok_ls_classes.id
            $table->unsignedBigInteger('ls_class_id');

            // Link to users.id (student)
            $table->unsignedBigInteger('student_id');

            $table->timestamps();

            // Prevent duplicates
            $table->unique(['ls_class_id', 'student_id']);

            // Foreign keys
            $table->foreign('ls_class_id')
                  ->references('id')->on('tok_ls_classes')
                  ->onDelete('cascade');

            $table->foreign('student_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ls_class_student');
    }
};