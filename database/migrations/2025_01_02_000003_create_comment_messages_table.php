<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('comment_messages', function (Blueprint $t) {
            $t->id();

            // Message belongs to a thread
            $t->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();

            // Author (teacher or student)
            $t->foreignId('author_id')->constrained('users')->cascadeOnDelete();

            // The message body (plain text or sanitized HTML)
            $t->text('body');

            $t->timestamps();

            $t->index(['comment_id', 'created_at']);
            $t->index('author_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('comment_messages');
    }
};