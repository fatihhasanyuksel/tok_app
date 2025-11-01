<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('comment_anchors', function (Blueprint $t) {
            $t->id();

            // Anchor belongs to a thread (comment)
            $t->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();

            // Character offsets into the version's body_html (plain-text offset after stripping tags on the client)
            $t->unsignedInteger('start_offset');
            $t->unsignedInteger('end_offset');

            // Short context hashes to help re-attach after edits (e.g., hash of 20 chars before/after)
            $t->string('before_hash', 64)->nullable();
            $t->string('after_hash', 64)->nullable();

            $t->timestamps();

            $t->index(['comment_id']);
            $t->index(['start_offset', 'end_offset']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('comment_anchors');
    }
};