<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tok_ls_lessons', function (Blueprint $table) {
            // Add content column AFTER title
            $table->longText('content')->nullable()->after('title');

            // Add status and published_at if missing
            if (!Schema::hasColumn('tok_ls_lessons', 'status')) {
                $table->string('status')->default('draft')->after('content');
            }
            if (!Schema::hasColumn('tok_ls_lessons', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tok_ls_lessons', function (Blueprint $table) {
            $table->dropColumn(['content', 'status', 'published_at']);
        });
    }
};