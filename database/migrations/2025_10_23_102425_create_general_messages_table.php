<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('general_messages')) {
            Schema::create('general_messages', function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->unsignedBigInteger('submission_id');
                $table->unsignedBigInteger('sender_id');
                $table->text('body');

                $table->timestamps();

                // Indexes
                $table->index(['submission_id', 'created_at']);
                $table->index(['sender_id', 'created_at']);

                // Foreign keys
                $table->foreign('submission_id')
                    ->references('id')->on('submissions')
                    ->cascadeOnDelete();

                $table->foreign('sender_id')
                    ->references('id')->on('users')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('general_messages');
    }
};