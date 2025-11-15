<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('versions', function (Blueprint $table) {
            // Only add if it's not already there
            if (!Schema::hasColumn('versions', 'created_by')) {
                $table->unsignedBigInteger('created_by')
                      ->nullable()
                      ->after('submission_id');

                // Optional FK constraint – if the user is deleted, keep the version but null the author
                $table->foreign('created_by')
                      ->references('id')
                      ->on('users')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('versions', function (Blueprint $table) {
            if (Schema::hasColumn('versions', 'created_by')) {
                // Drop FK first if it exists
                try {
                    $table->dropForeign(['created_by']);
                } catch (\Throwable $e) {
                    // Swallow in case FK name differs on some systems
                }

                $table->dropColumn('created_by');
            }
        });
    }
};