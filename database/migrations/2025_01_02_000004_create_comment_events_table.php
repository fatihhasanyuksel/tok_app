<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('comment_events', function (Blueprint $t) {
            $t->id();

            // Related comment thread
            $t->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();

            // Who triggered the event
            $t->foreignId('triggered_by')->constrained('users')->cascadeOnDelete();

            // Event type: created, seen, replied, revised, approved, reopened, outdated
            $t->string('event', 30);

            $t->timestamps();

            $t->index(['comment_id', 'event']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('comment_events');
    }
};