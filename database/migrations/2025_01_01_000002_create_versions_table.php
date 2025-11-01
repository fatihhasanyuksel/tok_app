<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $t->longText('body_html')->nullable();   // editor content snapshot
            $t->json('files_json')->nullable();      // any pasted/uploaded images metadata
            $t->timestamps();                        // created_at = version timestamp
        });
    }

    public function down(): void {
        Schema::dropIfExists('versions');
    }
};