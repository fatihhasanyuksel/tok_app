<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('versions', function (Blueprint $table) {
            $table->boolean('is_milestone')->default(false)->after('body_html')->index();
            $table->string('milestone_note', 140)->nullable()->after('is_milestone');
        });
    }

    public function down(): void
    {
        Schema::table('versions', function (Blueprint $table) {
            $table->dropColumn(['is_milestone', 'milestone_note']);
        });
    }
};