<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Add parent contact fields (nullable so nothing breaks)
            $table->string('parent_name')->nullable()->after('email');
            $table->string('parent_email')->nullable()->after('parent_name');
            $table->string('parent_phone')->nullable()->after('parent_email');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['parent_name', 'parent_email', 'parent_phone']);
        });
    }
};