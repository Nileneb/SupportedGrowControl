<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('rating')->nullable(); // 1–5, optional
            $table->string('context')->nullable(); // e.g., "growdash", "ui", "hardware", "agent"
            $table->text('message');              // the actual feedback
            $table->json('meta')->nullable();     // optional: browser, device_id, board_type, etc.

            $table->timestamps();
            
            $table->index('user_id');
            $table->index('context');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
