<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rich_text_blocks', function (Blueprint $table) {
            $table->id();

            // The model that owns this content (e.g., exhibition, essay)
            $table->string('owner_type', 64);         // 'exhibition' | 'essay'
            $table->unsignedBigInteger('owner_id');

            // Optional version label (for explicit saves)
            $table->string('version_label', 100)->nullable();

            // Canonical ProseMirror JSON + rendered HTML snapshot
            $table->json('pm_json');                  // TipTap document content
            $table->longText('html');                 // Sanitized HTML snapshot

            // Basic metrics
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedInteger('char_count')->default(0);

            // Audit (who created/updated)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Lookups
            $table->index(['owner_type', 'owner_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rich_text_blocks');
    }
};