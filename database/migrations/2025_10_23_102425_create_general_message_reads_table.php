<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('general_message_reads')) {
            Schema::create('general_message_reads', function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->unsignedBigInteger('message_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamp('read_at')->nullable();

                $table->timestamps();

                $table->unique(['message_id', 'user_id']);

                // Indexes
                $table->index(['user_id', 'read_at']);
                $table->index('message_id');

                // Foreign keys
                $table->foreign('message_id')
                    ->references('id')->on('general_messages')
                    ->cascadeOnDelete();

                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('general_message_reads');
    }
};