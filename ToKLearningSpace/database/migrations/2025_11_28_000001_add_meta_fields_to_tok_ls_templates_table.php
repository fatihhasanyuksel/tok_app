<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tok_ls_templates', function (Blueprint $table) {
            // New meta fields for templates
            $table->string('topic')->nullable()->after('title');
            $table->integer('duration_minutes')->nullable()->after('topic');
            $table->text('notes')->nullable()->after('content_text');
            $table->boolean('is_published')->default(false)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('tok_ls_templates', function (Blueprint $table) {
            $table->dropColumn(['topic', 'duration_minutes', 'notes', 'is_published']);
        });
    }
};