<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('threads', function (Blueprint $table) {
            $table->bigIncrements('id');

            // If you have a submissions table, you can later add a FK; for now keep it generic & safe.
            $table->unsignedBigInteger('submission_id')->index();

            // Original offset storage
            $table->text('selection_text')->nullable();
            $table->unsignedInteger('start_offset')->nullable();
            $table->unsignedInteger('end_offset')->nullable();

            // We’ll let your existing 2025_11_03_101024_add_pm_positions_to_threads.php add these,
            // so do NOT add pm_from / pm_to here (to avoid duplicate-column issues).
            // $table->unsignedInteger('pm_from')->nullable();
            // $table->unsignedInteger('pm_to')->nullable();

            $table->boolean('is_resolved')->default(false);
            $table->timestamps();

            // Optional safety indexes
            $table->index(['submission_id', 'is_resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('threads');
    }
};