<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tok_ls_classes', function (Blueprint $table) {
            $table->id();

            // Teacher who owns this class
            $table->unsignedBigInteger('teacher_id');

            // Class name (e.g., "11A")
            $table->string('name');

            // Year column (currently unused, can keep nullable for future use)
            $table->string('year')->nullable();

            $table->timestamps();

            // FK to users table
            $table->foreign('teacher_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tok_ls_classes');
    }
};