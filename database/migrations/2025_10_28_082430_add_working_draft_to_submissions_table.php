<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            // Plaintext working draft (autosave writes here)
            $table->longText('working_body')->nullable()->after('status');
            $table->timestamp('working_updated_at')->nullable()->after('working_body');

            // Optional pointer to the last explicit snapshot version
            $table->unsignedBigInteger('last_snapshot_version_id')->nullable()->after('working_updated_at');

            $table->foreign('last_snapshot_version_id')
                  ->references('id')->on('versions')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropForeign(['last_snapshot_version_id']);
            $table->dropColumn(['working_body', 'working_updated_at', 'last_snapshot_version_id']);
        });
    }
};