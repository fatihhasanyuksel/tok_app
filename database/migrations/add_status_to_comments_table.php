<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only add the column if it doesn't already exist
        if (! Schema::hasColumn('comments', 'status')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->string('status', 20)->default('open')->after('author_id');
            });
        }
    }

    public function down(): void
    {
        // Only drop the column if it exists
        if (Schema::hasColumn('comments', 'status')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};