<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            // Start at 1 so "0" can mean "unknown/unset" in future if needed
            $table->unsignedBigInteger('working_rev')
                  ->default(1)
                  ->after('working_html')
                  ->index();

            // Optional, helpful for audits; no FK to keep it lightweight
            $table->unsignedBigInteger('updated_by')
                  ->nullable()
                  ->after('working_rev')
                  ->index();
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn(['working_rev', 'updated_by']);
        });
    }
};