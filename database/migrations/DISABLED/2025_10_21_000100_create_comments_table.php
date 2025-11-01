<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // If already created by another migration, skip.
        if (Schema::hasTable('comments')) {
            return;
        }

        Schema::create('comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('version_id')->constrained('versions')->cascadeOnDelete();
            $t->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $t->string('status', 20)->default('open');
            $t->text('selection_text')->nullable();
            $t->timestamps();

            $t->index(['version_id', 'status']);
            $t->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};