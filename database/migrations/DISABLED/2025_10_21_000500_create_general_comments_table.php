<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('general_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('versions')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at_by_student')->nullable();
            $table->timestamps();

            $table->index(['version_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_comments');
    }
};