<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('submissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->string('type', 20); // 'exhibition' | 'essay'
            $t->string('status', 20)->default('draft'); // 'draft'|'submitted'|'changes'|'final'
            $t->timestamp('due_at')->nullable();
            $t->timestamps();

            $t->unique(['student_id','type']); // one of each per student
            $t->index(['type','status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('submissions');
    }
};