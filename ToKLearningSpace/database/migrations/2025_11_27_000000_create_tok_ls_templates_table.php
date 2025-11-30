<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tok_ls_templates', function (Blueprint $table) {
            $table->id();

            // Core template fields
            $table->string('title');
            $table->text('objectives')->nullable();
            $table->text('success_criteria')->nullable();

            // Lesson HTML content (TipTap)
            $table->longText('content_html')->nullable(); // formatted
            $table->longText('content_text')->nullable(); // plain text

            // Meta information
            $table->unsignedBigInteger('created_by');   // teacher/admin who authored it
            $table->unsignedBigInteger('updated_by')->nullable();

            // Soft delete NOT required (as discussed) → hard delete is allowed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tok_ls_templates');
    }
};