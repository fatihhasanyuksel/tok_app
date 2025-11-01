<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comment_anchors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->unsignedInteger('start_offset');     // character offset in plain text
            $table->unsignedInteger('end_offset');       // inclusive end
            $table->string('before_hash', 64)->nullable(); // optional context hash
            $table->string('after_hash', 64)->nullable();  // optional context hash
            $table->timestamps();

            $table->index('comment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_anchors');
    }
};