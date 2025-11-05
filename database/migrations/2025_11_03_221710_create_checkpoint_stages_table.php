<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkpoint_stages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();      // e.g. 'no_submission', 'draft_1', ...
            $table->string('label');              // e.g. 'No submission', 'Draft 1'
            $table->unsignedInteger('display_order')->nullable();
            $table->boolean('is_active')->default(true);
            // no timestamps
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoint_stages');
    }
};